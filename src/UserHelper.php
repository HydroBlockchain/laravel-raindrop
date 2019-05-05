<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\LaravelHydroRaindrop\Contracts\UserHelper as UserHelperInterface;
use Adrenth\Raindrop\Client;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroHelper
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
final class UserHelper implements UserHelperInterface
{
    /**
     * @var Model
     */
    private $user;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->user = resolve(config('hydro-raindrop.user_model_class'));
    }

    /**
     * @param Model $user
     * @return UserHelper
     */
    public function setUser(Model $user): UserHelper
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->user->getAttribute('hydro_raindrop_blocked') !== null;
    }

    /**
     * Enable Hydro Raindrop MFA for user.
     *
     * @return void
     */
    public function enableHydro(): void
    {
        $this->user->forceFill([
            'hydro_raindrop_enabled' => now(),
            'hydro_raindrop_confirmed' => null,
            'hydro_raindrop_blocked' => null,
            'hydro_raindrop_failed_attempts' => 0,
        ])->save();
    }

    /**
     * {@inheritDoc}
     */
    public function unregisterHydro(string $hydroId): void
    {
        $this->client->unregisterUser($hydroId);

        $this->user->forceFill([
            'hydro_id' => null,
            'hydro_raindrop_enabled' => null,
            'hydro_raindrop_confirmed' => null,
            'hydro_raindrop_blocked' => null,
            'hydro_raindrop_failed_attempts' => 0,
        ])->save();
    }

    /**
     * {@inheritDoc}
     */
    public function unblock(): void
    {
        $this->user->forceFill([
            'hydro_raindrop_blocked' => null,
            'hydro_raindrop_failed_attempts' => 0,
        ])->save();
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->user->forceFill([
            'hydro_id' => null,
            'hydro_raindrop_enabled' => null,
            'hydro_raindrop_confirmed' => null,
            'hydro_raindrop_blocked' => null,
            'hydro_raindrop_failed_attempts' => 0,
        ])->save();
    }

    /**
     * {@inheritDoc}
     */
    public function requiresMfa(): bool
    {
        $hydroId = $this->user->getAttribute('hydro_id');
        $mfaEnabled = (bool) $this->user->getAttribute('hydro_raindrop_enabled');
        $mfaConfirmed = (bool) $this->user->getAttribute('hydro_raindrop_confirmed');

        return (!empty($hydroId) && $mfaEnabled && $mfaConfirmed)
            || (!empty($hydroId) && !$mfaEnabled && !$mfaConfirmed);
    }

    /**
     * {@inheritDoc}
     */
    public function requiresMfaSetup(): bool
    {
        switch (config('hydro-raindrop.mfa_method', 'prompted')) {
            case 'optional':
                return false;
            case 'prompted':
            case 'enforced':
                return !$this->requiresMfa();
        }

        return false;
    }
}
