<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

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

        // Start new session
        if (!$this->session->isStarted()
            && !$this->session->isVerified()
        ) {
            $this->session->start();
        }

        if ($this->session->isVerified()) {
            return $next($request);
        }

        $response = $this->handler->handle($user);

        if ($response) {
            return $response;
        }

        return $next($request);
    }
}
