<?php

use Faker\Factory;
use Faker\Generator;

if (! function_exists('faker')) {
    /**
     * A shorthand for faker factory.
     */
    function faker(): Generator
    {
        return Factory::create();
    }
}
