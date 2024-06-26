<?php

namespace Shetabit\Agent\Facades;

use Illuminate\Support\Facades\Facade;

class Agent extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'agent';
    }
}
