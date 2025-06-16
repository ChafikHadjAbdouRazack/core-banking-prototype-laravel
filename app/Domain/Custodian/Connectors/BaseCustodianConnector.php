<?php

declare(strict_types=1);

namespace App\Domain\Custodian\Connectors;

use App\Domain\Custodian\Contracts\ICustodianConnector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;

abstract class BaseCustodianConnector implements ICustodianConnector
{
    protected array $config;
    protected PendingRequest $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeClient();
    }

    protected function initializeClient(): void
    {
        $this->client = Http::baseUrl($this->config['base_url'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->withHeaders($this->getHeaders());

        if ($this->config['verify_ssl'] ?? true) {
            $this->client->withOptions(['verify' => true]);
        }
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (isset($this->config['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . $this->config['api_key'];
        }

        return array_merge($headers, $this->config['headers'] ?? []);
    }

    public function getName(): string
    {
        return $this->config['name'] ?? class_basename($this);
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->client->get($this->getHealthCheckEndpoint());
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Custodian {$this->getName()} health check failed", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function logRequest(string $method, string $endpoint, array $data = []): void
    {
        if ($this->config['debug'] ?? false) {
            Log::debug("Custodian {$this->getName()} request", [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data,
            ]);
        }
    }

    protected function logResponse(string $method, string $endpoint, $response): void
    {
        if ($this->config['debug'] ?? false) {
            Log::debug("Custodian {$this->getName()} response", [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }
    }

    abstract protected function getHealthCheckEndpoint(): string;
}