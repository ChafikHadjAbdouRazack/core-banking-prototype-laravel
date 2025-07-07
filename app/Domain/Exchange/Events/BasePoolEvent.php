<?php

namespace App\Domain\Exchange\Events;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

abstract class BasePoolEvent extends ShouldBeStored
{
    public readonly array $eventMetadata;

    public function __construct()
    {
        $this->eventMetadata = $this->generateMetadata();
    }

    protected function generateMetadata(): array
    {
        return [
            'event_id'       => \Illuminate\Support\Str::uuid()->toString(),
            'event_type'     => class_basename($this),
            'timestamp'      => now()->toIso8601String(),
            'user_id'        => Auth::id(),
            'user_type'      => Auth::user()?->getMorphClass(),
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'session_id'     => session()->getId(),
            'request_id'     => Request::header('X-Request-ID'),
            'correlation_id' => Request::header('X-Correlation-ID'),
            'environment'    => app()->environment(),
            'version'        => config('app.version', '1.0.0'),
        ];
    }

    public function getEventMetadata(): array
    {
        return $this->eventMetadata;
    }

    public function withAdditionalMetadata(array $metadata): self
    {
        $this->eventMetadata = array_merge($this->eventMetadata, $metadata);

        return $this;
    }
}
