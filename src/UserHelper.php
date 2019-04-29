<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop;

use Adrenth\Raindrop\Client;
use Adrenth\Raindrop\Exception\UnregisterUserFailed;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroHelper
 *
 * @package Adrenth\LaravelHydroRaindrop
 */
final class UserHelper
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
     * @param Model $user
     */
    public function __construct(Model $user)
    {
        $this->user = $user;
        $this->client = resolve(Client::class);
    }

    /**
     * @param Model $user
     * @return UserHelper
     */
    public static function create(Model $user): UserHelper
    {
        return new self($user);
    }

    /**
     * Enable Hydro Raindrop MFA for user.
     *
     * @return void
     */
    public function enableHydro(): void
    {
        $this->user->update([
            'hydro_raindrop_enabled' => now(),
            'hydro_raindrop_confirmed' => null,
            'hydro_raindrop_failed_attempts' => 0,
        ]);
    }

    /**
     * Disable Hydro Raindrop MFA for user.
     *
     * @return void
     * @throws UnregisterUserFailed
     */
    public function unregisterHydro(): void
    {
        $hydroId = $this->user->getAttribute('hydro_id');

        $this->client->unregisterUser($hydroId);

        $this->user->update([
            'hydro_id' => null,
            'hydro_raindrop_enabled' => null,
            'hydro_raindrop_confirmed' => null,
            'hydro_raindrop_failed_attempts' => 0,
        ]);
    }

    /**
     * @return bool
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
     * @return bool
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
