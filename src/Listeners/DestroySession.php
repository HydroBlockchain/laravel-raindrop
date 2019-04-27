<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Listeners;

use Adrenth\LaravelHydroRaindrop\MfaSession;
use Illuminate\Auth\Events\Logout;

/**
 * Class DestroySession
 *
 * @package Adrenth\LaravelHydroRaindrop\Listeners
 */
class DestroySession
{
    /**
     * @var MfaSession
     */
    private $session;

    /**
     * @param MfaSession $session
     */
    public function __construct(MfaSession $session)
    {
        $this->session = $session;
    }

    /**
     * @param Logout $event
     */
    public function handle(Logout $event): void
    {
        $this->session->destroy();
    }
}
