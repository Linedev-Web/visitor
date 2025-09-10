<?php

use Shetabit\Visitor\Visitor;

if (! function_exists('visitor')) {
    /**
     * Access visitor through helper.
     *
     * @return Visitor
     */
    function visitor()
    {
        return app('shetabit-visitor');
    }
}
