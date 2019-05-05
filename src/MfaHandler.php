<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\LaravelHydroRaindrop\Contracts\UserHelper as UserHelperInterface;
use Adrenth\Raindrop\Client;
use Adrenth\Raindrop\Exception\RegisterUserFailed;
use Adrenth\Raindrop\Exception\UnregisterUserFailed;
use Adrenth\Raindrop\Exception\UserAlreadyMappedToApplication;
use Adrenth\Raindrop\Exception\UsernameDoesNotExist;
use Adrenth\Raindrop\Exception\VerifySignatureFailed;
use Adrenth\LaravelHydroRaindrop\Events;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Handle a unverified request.
     *
     * Two possible scenario's will be handled by this method:
     * - User account requires MFA Setup; the setup screen will be displayed.
     * - Request requires MFA; the MFA screen will be displayed.
     *
     * @param Model $user
     * @return Response|null
     * @throws Exception
     */
    public function handle(Model $user): ?Response
    {
        $userHelper = $this->getUserHelper($user);

        if ($userHelper->requiresMfaSetup()) {
            $this->log->debug(sprintf(
                'Hydro Raindrop: User %d is required to setup Hydro Raindrop MFA.',
                $user->getKey()
            ));

            if ($this->request->has('hydro_cancel') && $this->mfaMethodIsEnforced()) {
                Auth::logout();
                return redirect()->route(config('hydro-raindrop.login_route_name', 'login'));
            }

            if ($this->request->has('hydro_skip') && !$this->mfaMethodIsEnforced()) {
                return null;
            }

            $response = $this->handleMfaSetup($user);

            if ($response !== null) {
                return $response;
            }
        }

        if ($userHelper->requiresMfa()) {
            if ($this->request->has('hydro_cancel')) {
                Auth::logout();
                return redirect()->route(config('hydro-raindrop.login_route_name', 'login'));
            }

            return $this->handleMfa($user);
        }

        return null;
    }

    /**
     * Will fire the `UserLoginIsBlocked` event and logout the user and redirect
     * back to the login form.
     *
     * @param Model $user
     * @return Response
     */
    public function handleBlocked(Model $user): Response
    {
        /*
         * Listening to this event allows developers to generate a Flash message
         * or something similar to inform users why they cannot login.
         */
        event(new Events\UserLoginIsBlocked($user));

        Auth::logout();

        return redirect()->route(config('hydro-raindrop.login_route_name', 'login'));
    }

    /**
     * Handle the MFA request for given User.
     *
     * @param Model $user
     * @return Response|null
     * @throws Exception
     */
    private function handleMfa(Model $user): ?Response
    {
        $error = null;

        if (!$this->session->isValid()) {
            $error = trans('Session has expired. A new Security Code has been generated. Please retry.');
            $this->session->start();
        } elseif ($this->request->has('hydro_verify')) {
            try {
                $response = $this->client->verifySignature(
                    $user->getAttribute('hydro_id'),
                    $this->session->getMessage()
                );

                if ($user->getAttribute('hydro_raindrop_enabled') === null) {
                    $user->setAttribute('hydro_raindrop_enabled', date('Y-m-d H:i:s', $response->getTimestamp()));
                }

                if ($user->getAttribute('hydro_raindrop_confirmed') === null) {
                    $user->setAttribute('hydro_raindrop_confirmed', date('Y-m-d H:i:s', $response->getTimestamp()));
                }

                $user->setAttribute('hydro_raindrop_failed_attempts', 0);
                $user->save();

                $this->session->destroy();
                $this->session->setVerified();

                event(new Events\SignatureVerified($user));

                return redirect()->to($this->request->getPathInfo());
            } catch (VerifySignatureFailed $e) {
                $response = $this->handleFailedMfaAttempt($user);

                if ($response) {
                    return $response;
                }

                $this->session->regenerateMessage();

                $error = trans('Verification failed. A new Security Code has been generated. Please retry.');
            }
        }

        return response()->view('hydro-raindrop::mfa.mfa', [
            'message' => $this->session->getMessage(),
            'error' => $error,
        ]);
    }

    /**
     * Handle MFA setup for given User.
     *
     * @param Model $user
     * @return Response|null
     */
    private function handleMfaSetup(Model $user): ?Response
    {
        $hydroId = $this->request->get('hydro_id');

        if ($hydroId === null) {
            return response()->view('hydro-raindrop::mfa.setup', [
                'mfaMethod' => $this->getMfaMethod(),
            ]);
        }

        $validator = Validator::make(
            ['hydro_id' => $hydroId],
            ['hydro_id' => 'min:3|max:32|required']
        );

        if ($validator->fails()) {
            return response()->view('hydro-raindrop::mfa.setup', [
                'error' => trans('Please provide a valid HydroID.'),
                'mfaMethod' => config('hydro-raindrop.mfa_method', 'prompted')
            ]);
        }

        try {
            $this->client->registerUser($hydroId);

            $user->forceFill([
                'hydro_id' => $hydroId,
            ])->save();

            event(new Events\HydroIdRegistered($user, $hydroId));

            $this->log->debug(sprintf(
                'Hydro Raindrop: User %d has successfully registered a HydroID.',
                $user->getKey()
            ));
        } catch (UserAlreadyMappedToApplication $e) {
            event(new Events\HydroIdAlreadyMapped($user, $hydroId));
            $error = $this->userAlreadyMappedToApplication($user, $hydroId);
        } catch (UsernameDoesNotExist $e) {
            event(new Events\HydroIdDoesNotExist($user, $hydroId));
            $error = trans('HydroID does not exist. Please review your input and try again.');
        } catch (RegisterUserFailed $e) {
            event(new Events\HydroIdRegistrationFailed($user, $hydroId));
            $this->log->error($e->getMessage());
            $error = trans('Due to a system error your HydroID could not be registered.');
        }

        if (isset($error)) {
            return response()->view('hydro-raindrop::mfa.setup', [
                'error' => $error,
                'mfaMethod' => $this->getMfaMethod(),
            ]);
        }

        return null;
    }

    /**
     * Handle a failed MFA attempt.
     *
     * This will increase the `hydro_raindrop_failed_attempts` attribute on the
     * User model and may logout the user if the configured maximum failed
     * attempts have been exceeded.
     *
     * May return a RedirectResponse which routes to the login form.
     *
     * @param Model $user
     * @return RedirectResponse|null
     */
    private function handleFailedMfaAttempt(Model $user): ?RedirectResponse
    {
        event(new Events\SignatureFailed($user));

        $user->setAttribute(
            'hydro_raindrop_failed_attempts',
            $user->getAttribute('hydro_raindrop_failed_attempts') + 1
        );

        $user->save();

        $maximumAttempts = config('hydro-raindrop.mfa_maximum_attempts', 0);

        if ($maximumAttempts > 0
            && $user->getAttribute('hydro_raindrop_failed_attempts') > $maximumAttempts
        ) {
            $user->forceFill([
                'hydro_raindrop_failed_attempts' => 0,
                'hydro_raindrop_blocked' => now(),
            ])->save();

            event(new Events\UserIsBlocked($user));

            Auth::logout();

            return redirect()->route(config('hydro-raindrop.login_route_name', 'login'));
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
            event(new Events\HydroIdRegistered($user, $hydroId));

            $userHelper = $this->getUserHelper($user);
            $userHelper->unregisterHydro($hydroId);
        } catch (UnregisterUserFailed $e) {
            $this->log->error(sprintf(
                'Hydro Raindrop: Unregistering user %s failed: %s',
                $hydroId,
                $e->getMessage()
            ));
        }

        return trans(
            'Your HydroID was already mapped to this site. '
            . 'Mapping is now removed. Refresh your accounts in the Hydro app '
            . 'and tap "Add New Account" and follow the instructions. '
        );
    }

    /**
     * @return string
     */
    public function getMfaMethod(): string
    {
        return (string) config('hydro-raindrop.mfa_method', 'prompted');
    }

    /**
     * @return bool
     */
    public function mfaMethodIsEnforced(): bool
    {
        return $this->getMfaMethod() === 'enforced';
    }

    /**
     * @param Model $user
     * @return UserHelperInterface
     */
    public function getUserHelper(Model $user): UserHelperInterface
    {
        $userHelper = resolve(UserHelperInterface::class);

        if ($userHelper instanceof UserHelper) {
            $userHelper->setUser($user);
        }

        return $userHelper;
    }
}
