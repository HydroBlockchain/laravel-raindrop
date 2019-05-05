<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Contracts;

use Adrenth\Raindrop\Exception\UnregisterUserFailed;

/**
 * Interface UserHelper
 *
 * @package Adrenth\LaravelHydroRaindrop\Contracts
 */
interface UserHelper
{
    /**
     * @return bool
     */
    public function isBlocked(): bool;

    /**
     * Enable Hydro Raindrop MFA for user.
     *
     * @return void
     */
    public function enableHydro(): void;

    /**
     * Disable Hydro Raindrop MFA for user.
     *
     * @param string $hydroId
     * @return void
     * @throws UnregisterUserFailed
     */
    public function unregisterHydro(string $hydroId): void;

    /**
     * Unblock user which was blocked due too many failed MFA attempts.
     *
     * @return void
     */
    public function unblock(): void;

    /**
     * Reset all user hydro raindrop data.
     *
     * @return void
     */
    public function reset(): void;

    /**
     * Whether the user needs to perform MFA.
     *
     * @return bool
     */
    public function requiresMfa(): bool;

    /**
     * Whether the user needs to set-tup their HydroID.
     *
     * @return bool
     */
    public function requiresMfaSetup(): bool;
}
