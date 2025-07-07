<?php

use Faker\Factory;
use Faker\Generator;

if (!function_exists('faker')) {
    /**
     * A shorthand for faker factory
     *
     * @return \Faker\Generator
     */
    function faker(): Generator
    {
        return Factory::create();
    }
}
