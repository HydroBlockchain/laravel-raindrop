<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\LaravelHydroRaindrop\Events\UserMfaSessionStarted;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class Middleware
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
final class Middleware
{
    /**
     * @var MfaHandler
     */
    private $handler;

    /**
     * @var MfaSession
     */
    private $session;

    /**
     * @param MfaHandler $handler
     * @param MfaSession $session
     */
    public function __construct(MfaHandler $handler, MfaSession $session)
    {
        $this->handler = $handler;
        $this->session = $session;
    }

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws Exception
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        /*
         * 1. Check if User Account is blocked due to many failed MFA attempts.
         */
        $userHelper = $this->handler->getUserHelper($user);

        if ($userHelper->isBlocked()) {
            return $this->handler->handleBlocked($user);
        }

        /*
         * 2. Start new MFA session (if applicable).
         */
        if (!$this->session->isStarted()
            && !$this->session->isVerified()
        ) {
            event(new UserMfaSessionStarted($user));
            $this->session->start();
        }

        /*
         * 3. MFA session is valid and verified.
         */
        if ($this->session->isVerified()) {
            return $next($request);
        }

        /*
         * 4. Handle the request using the MFA handler. If MFA or MFA setup is
         *    required a response will be return containing the MFA view or
         *    MFA setup view.
         */
        $response = $this->handler->handle($user);

        if ($response) {
            return $response;
        }

        return $next($request);
    }
}
