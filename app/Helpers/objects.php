<?php

use JustSteveKing\DataObjects\Contracts\DataObjectContract;
use JustSteveKing\DataObjects\Facades\Hydrator;

if (!function_exists('hydrate') )
{
    /**
     * Hydrate and return a specific Data Object class instance.
     * @template T of DataObjectContract
     *
     * @param class-string<T> $class
     * @param array $properties
     *
     * @return T
     */
	function hydrate(string $class, array $properties): DataObjectContract
	{
        return Hydrator::fill(
            class: $class,
            properties: collect($properties)->map(function($value) {
                return $value instanceof UnitEnum ? $value->value : $value;
            })->toArray()
        );
    }
}
