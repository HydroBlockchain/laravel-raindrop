<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Class TransferHydro
 *
 * @package Adrenth\LaravelHydroRaindrop\Console
 */
class TransferHydro extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'hydro-raindrop:transfer-hydro {userA} {userB}';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Transfer Hydro Raindrop MFA from user to another user.';

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $userModelClass = resolve(config('hydro-raindrop.user_model_class'));

            /** @var Model $userA */
            $userA = $userModelClass->findOrFail($this->argument('userA'));

            /** @var Model $userB */
            $userB = $userModelClass->findOrFail($this->argument('userB'));

            $userB->setAttribute(
                'hydro_id',
                $userA->getAttribute('hydro_id')
            );

            $userB->setAttribute(
                'hydro_raindrop_enabled',
                $userA->getAttribute('hydro_raindrop_enabled')
            );

            $userB->setAttribute(
                'hydro_raindrop_confirmed',
                $userA->getAttribute('hydro_raindrop_confirmed')
            );

            $userB->setAttribute(
                'hydro_raindrop_blocked',
                $userA->getAttribute('hydro_raindrop_blocked')
            );

            $userB->setAttribute(
                'hydro_raindrop_failed_attempts',
                $userA->getAttribute('hydro_raindrop_failed_attempts')
            );

            $userB->save();
        } catch (Throwable $e) {
            $this->output->error('Could not reset Hydro Raindrop MFA for user: ' . $e->getMessage());
        }
    }
}
