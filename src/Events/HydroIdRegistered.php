<?php

declare(strict_types=1);

namespace Adrenth\LaravelHydroRaindrop\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroIdRegistered
 *
 * @package Adrenth\LaravelHydroRaindrop\Events
 */
class HydroIdRegistered
{
    /**
     * @var Model
     */
    public $user;

    /**
     * @param Model $user
     */
    public function __construct(Model $user)
    {
        $this->user = $user;
    }
}
