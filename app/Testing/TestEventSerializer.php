<?php

namespace App\Testing;

use Carbon\Carbon;
use Spatie\EventSourcing\EventSerializers\EventSerializer;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class TestEventSerializer implements EventSerializer
{
    public function serialize(ShouldBeStored $event): string
    {
        $properties = [];
        $reflection = new \ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($event);
            
            // Handle Carbon instances
            if ($value instanceof Carbon) {
                $properties[$property->getName()] = $value->toIso8601String();
            } else {
                $properties[$property->getName()] = $value;
            }
        }

        return json_encode($properties);
    }

    public function deserialize(
        string $eventClass,
        string $json,
        int $version,
        ?string $metadata = null
    ): ShouldBeStored {
        $data = json_decode($json, true);

        // Create instance without constructor
        $event = (new \ReflectionClass($eventClass))->newInstanceWithoutConstructor();

        // Set properties directly
        foreach ($data as $property => $value) {
            if (property_exists($event, $property)) {
                $reflection = new \ReflectionProperty($event, $property);
                $reflection->setAccessible(true);
                
                // Handle Carbon type hints
                $type = $reflection->getType();
                if ($type && !$type->isBuiltin() && $type->getName() === Carbon::class) {
                    $value = Carbon::parse($value);
                }
                
                $reflection->setValue($event, $value);
            }
        }

        return $event;
    }
}
