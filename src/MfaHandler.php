<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\Raindrop\Client;
use Adrenth\Raindrop\Exception\RegisterUserFailed;
use Adrenth\Raindrop\Exception\UnregisterUserFailed;
use Adrenth\Raindrop\Exception\UserAlreadyMappedToApplication;
use Adrenth\Raindrop\Exception\UsernameDoesNotExist;
use Adrenth\Raindrop\Exception\VerifySignatureFailed;
use Adrenth\LaravelHydroRaindrop\Events\HydroIdAlreadyMapped;
use Adrenth\LaravelHydroRaindrop\Events\HydroIdRegistered;
use Adrenth\LaravelHydroRaindrop\Events\SignatureFailed;
use Adrenth\LaravelHydroRaindrop\Events\SignatureVerified;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MfaHandler
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
final class MfaHandler
{
    /**
     * @var MfaSession
     */
    private $session;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @param MfaSession $session
     * @param Request $request
     * @param Client $client
     * @param LoggerInterface $log
     */
    public function __construct(
        MfaSession $session,
        Request $request,
        Client $client,
        LoggerInterface $log
    ) {
        $this->session = $session;
        $this->request = $request;
        $this->client = $client;
        $this->log = $log;
    }

    /**
     * @param Model $user
     * @return Response|null
     * @throws Exception
     */
    public function handle(Model $user): ?Response
    {
        if ($this->userRequiresMfaSetup($user)) {
            $this->log->debug(sprintf(
                'Hydro Raindrop: User %d is required to setup Hydro Raindrop MFA.',
                $user->getKey()
            ));

            $response = $this->handleMfaSetup($user);

            if ($response !== null) {
                return $response;
            }
        }

        if ($this->userRequiresMfa($user)) {
            return $this->handleMfa($user);
        }

        return null;
    }

    /**
     * @param Model $user
     * @return Response|null
     * @throws Exception
     */
    private function handleMfa(Model $user): ?Response
    {
        $error = null;

        if (!$this->session->isValid()) {
            $error = 'Session has expired. A new Security Code has been generated. Please retry.';
            $this->session->start();
        } elseif ($this->request->has('hydro_verify')) {
            try {
                $this->client->verifySignature(
                    $user->getAttribute('hydro_id'),
                    $this->session->getMessage()
                );

                $user->setAttribute('is_raindrop_enabled', now());
                $user->setAttribute('is_raindrop_confirmed', now());
                $user->save();

                $this->session->destroy();
                $this->session->setVerified();

                event(new SignatureVerified($user));

                return redirect()->to($this->request->getPathInfo());
            } catch (VerifySignatureFailed $e) {
                event(new SignatureFailed($user));
                $this->session->regenerateMessage();
                $error = 'Verification failed. A new Security Code has been generated. Please retry.';
            }
        }

        return response()->view('hydro-raindrop::mfa.mfa', [
            'message' => $this->session->getMessage(),
            'error' => $error,
        ]);
    }

    /**
     * Handle MFA setup.
     *
     * @param Model $user
     * @return Response|null
     */
    private function handleMfaSetup(Model $user): ?Response
    {
        if ($this->request->has('hydro_id')) {
            $hydroId = $this->request->get('hydro_id');

            $validator = Validator::make(
                ['hydro_id' => $hydroId],
                ['hydro_id' => 'min:3|max:32|required']
            );

            if ($validator->fails()) {
                return response()->view('hydro-raindrop::mfa.setup', [
                    'error' => 'Please provide a valid HydroID.'
                ]);
            }

            try {
                $this->client->registerUser($hydroId);

                /** @var Model $user */
                $user->setAttribute('hydro_id', $hydroId);
                $user->save();

                event(new HydroIdRegistered($user));

                $this->log->debug(sprintf(
                    'Hydro Raindrop: User %d has successfully registered a HydroID.',
                    $user->getKey()
                ));
            } catch (UserAlreadyMappedToApplication $e) {
                event(new HydroIdAlreadyMapped($user, $hydroId));
                $error = $this->userAlreadyMappedToApplication($user, $hydroId);
            } catch (UsernameDoesNotExist $e) {
                $error = 'HydroID does not exist. Please review your input and try again.';
            } catch (RegisterUserFailed $e) {
                $this->log->error($e->getMessage());
                $error = 'Due to a system error your HydroID could not be registered.';
            }

            if (isset($error)) {
                return response()->view('hydro-raindrop::mfa.setup', [
                    'error' => $error
                ]);
            }
        } else {
            return response()->view('hydro-raindrop::mfa.setup');
        }

        return null;
    }

    /**
     * User is already mapped to this application.
     *
     * Edge case: A user tries to re-register with HydroID. If the user meta has
     * been deleted, the user can re-use his HydroID but needs to verify it again.
     *
     * @param Model $user
     * @param string $hydroId
     * @return string
     */
    private function userAlreadyMappedToApplication(Model $user, string $hydroId): string
    {
        $this->log->warning(sprintf(
            'Hydro Raindrop: HydroID %s is already mapped to this application.',
            $hydroId
        ));

        try {
            $this->client->unregisterUser($hydroId);

            $user->setAttribute('hydro_id', null);
            $user->setAttribute('is_raindrop_enabled', null);
            $user->setAttribute('is_raindrop_confirmed', null);
            $user->save();
        } catch (UnregisterUserFailed $e) {
            $this->log->error(sprintf(
                'Hydro Raindrop: Unregistering user %s failed: %s',
                $hydroId,
                $e->getMessage()
            ));
        }

        return 'Your HydroID was already mapped to this site. '
            . 'Mapping is removed. Please re-enter your HydroID to proceed.';
    }

    /**
     * @param Model $user
     * @return bool
     */
    private function userRequiresMfa(Model $user): bool
    {
        $hydroId = $user->getAttribute('hydro_id');
        $mfaEnabled = (bool) $user->getAttribute('is_raindrop_enabled');
        $mfaConfirmed = (bool) $user->getAttribute('is_raindrop_confirmed');

        return (!empty($hydroId) && $mfaEnabled && $mfaConfirmed)
            || (!empty($hydroId) && !$mfaEnabled && !$mfaConfirmed);
    }

    /**
     * @param Model $user
     * @return bool
     */
    public function userRequiresMfaSetup(Model $user): bool
    {
        return empty($user->getAttribute('hydro_id'));
    }
}
