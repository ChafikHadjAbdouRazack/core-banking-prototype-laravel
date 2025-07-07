<?php

namespace App\Testing;

use Spatie\EventSourcing\EventSerializers\EventSerializer;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;
use Spatie\EventSourcing\StoredEvents\StoredEvent;

class TestEventSerializer implements EventSerializer
{
    public function serialize(ShouldBeStored $event): string
    {
        $properties = [];
        $reflection = new \ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $properties[$property->getName()] = $property->getValue($event);
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
                $reflection->setValue($event, $value);
            }
        }

        return $event;
    }
}
