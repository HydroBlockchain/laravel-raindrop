<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Class ResetHydro
 *
 * @package Adrenth\LaravelHydroRaindrop\Console
 */
class ResetHydro extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'raindrop:reset-hydro {user}';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Reset Hydro Raindrop MFA for user.';

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
            $user->setAttribute('hydro_id', null);
            $user->setAttribute('hydro_raindrop_enabled', null);
            $user->setAttribute('hydro_raindrop_confirmed', null);
            $user->save();
        } catch (Throwable $e) {
            $this->output->error('Could not reset Hydro Raindrop MFA for user: ' . $e->getMessage());
        }
    }
}
