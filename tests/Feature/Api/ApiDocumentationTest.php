<?php

declare(strict_types=1);

it('can access API documentation', function () {
    $response = $this->get('/api/documentation');

    $response->assertOk();
});

it('can access API documentation JSON', function () {
    $response = $this->get('/docs/api-docs.json');

    $response->assertOk()
        ->assertJsonStructure([
            'openapi',
            'info' => [
                'title',
                'description',
                'contact',
                'license',
                'version',
            ],
            'servers',
            'paths',
            'components' => [
                'schemas',
                'securitySchemes',
            ],
            'tags',
        ]);
});

it('has correct API information', function () {
    $response = $this->get('/docs/api-docs.json');

    $response->assertOk()
        ->assertJsonPath('info.title', 'FinAegis Core Banking API')
        ->assertJsonPath('info.version', '1.0.0')
        ->assertJsonPath('info.license.name', 'Apache 2.0');
});

it('has all required endpoints documented', function () {
    $response = $this->get('/docs/api-docs.json');

    $json = $response->json();
    $paths = array_keys($json['paths']);

    expect($paths)->toContain('/api/accounts');
    expect($paths)->toContain('/api/accounts/{uuid}');
    expect($paths)->toContain('/api/accounts/{uuid}/freeze');
    expect($paths)->toContain('/api/accounts/{uuid}/unfreeze');
    expect($paths)->toContain('/api/accounts/{uuid}/deposit');
    expect($paths)->toContain('/api/accounts/{uuid}/withdraw');
    expect($paths)->toContain('/api/accounts/{uuid}/balance');
    expect($paths)->toContain('/api/accounts/{uuid}/balance/summary');
    expect($paths)->toContain('/api/transfers');
});

it('has all required schemas documented', function () {
    $response = $this->get('/docs/api-docs.json');

    $json = $response->json();
    $schemas = array_keys($json['components']['schemas']);

    expect($schemas)->toContain('Account');
    expect($schemas)->toContain('Transaction');
    expect($schemas)->toContain('Transfer');
    expect($schemas)->toContain('Balance');
    expect($schemas)->toContain('Error');
});

it('has security scheme defined', function () {
    $response = $this->get('/docs/api-docs.json');

    $response->assertOk()
        ->assertJsonPath('components.securitySchemes.sanctum.type', 'http')
        ->assertJsonPath('components.securitySchemes.sanctum.scheme', 'bearer');
});

it('has proper tags defined', function () {
    $response = $this->get('/docs/api-docs.json');

    $json = $response->json();
    $tags = array_column($json['tags'], 'name');

    expect($tags)->toContain('Accounts');
    expect($tags)->toContain('Transactions');
    expect($tags)->toContain('Transfers');
    expect($tags)->toContain('Balance');
});