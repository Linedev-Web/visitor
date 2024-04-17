<?php

namespace Shetabit\Visitor\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class viewer
 */
class Visitor extends Facade
{
    /**
     * Get the registered name of the component.
     */
    public static function getFacadeAccessor(): string
    {
        return 'shetabit-visitor';
    }
}
