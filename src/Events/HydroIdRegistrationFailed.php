<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroIdRegistrationFailed
 *
 * @package Adrenth\LaravelHydroRaindrop\Events
 */
class HydroIdRegistrationFailed
{
    /**
     * @var Model
     */
    public $user;

    /**
     * @var string
     */
    public $hydroId;

    /**
     * @param Model $user
     * @param string $hydroId
     */
    public function __construct(Model $user, string $hydroId)
    {
        $this->user = $user;
        $this->hydroId = $hydroId;
    }
}
