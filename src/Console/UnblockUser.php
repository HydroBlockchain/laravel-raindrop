<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Console;

use Adrenth\LaravelHydroRaindrop\MfaHandler;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Class UnblockUser
 *
 * @package Adrenth\LaravelHydroRaindrop\Console
 */
class UnblockUser extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'hydro-raindrop:unblock-user {user}';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Unblock given user which was blocked due too many failed MFA attempts.';

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $userModelClass = resolve(config('hydro-raindrop.user_model_class'));

            /** @var Model $user */
            $user = $userModelClass->findOrFail($this->argument('user'));

            /** @var MfaHandler $mfaHandler */
            $mfaHandler = resolve(MfaHandler::class);

            $userHelper = $mfaHandler->getUserHelper($user);
            $userHelper->unblock();
        } catch (Throwable $e) {
            $this->output->error('Could not reset Hydro Raindrop MFA for user: ' . $e->getMessage());
        }
    }
}
