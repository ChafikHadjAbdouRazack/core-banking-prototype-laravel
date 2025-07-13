<?php

use App\Http\Middleware\CachePerformance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('can handle request and track cache performance', function () {
    Cache::put('cache_performance:hits', 10);
    Cache::put('cache_performance:misses', 5);

    $request = Request::create('/test', 'GET');
    $middleware = new CachePerformance;

    $response = $middleware->handle($request, function ($req) {
        // Simulate some cache activity during request
        Cache::put('cache_performance:hits', 12);
        Cache::put('cache_performance:misses', 6);

        return new Response('Test response');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->get('X-Cache-Hits'))->toBe('2');
    expect($response->headers->get('X-Cache-Misses'))->toBe('1');
    expect($response->headers->get('X-Cache-Hit-Rate'))->toBe('66.67%');
});

it('handles zero cache activity gracefully', function () {
    Cache::put('cache_performance:hits', 0);
    Cache::put('cache_performance:misses', 0);

    $request = Request::create('/test', 'GET');
    $middleware = new CachePerformance;

    $response = $middleware->handle($request, function ($req) {
        return new Response('Test response');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->headers->has('X-Cache-Hits'))->toBeFalse();
    expect($response->headers->has('X-Cache-Misses'))->toBeFalse();
    expect($response->headers->has('X-Cache-Hit-Rate'))->toBeFalse();
});

it('calculates cache hit rate correctly for low performance', function () {
    Cache::put('cache_performance:hits', 0);
    Cache::put('cache_performance:misses', 0);

    $request = Request::create('/test-endpoint', 'GET');
    $middleware = new CachePerformance;

    $response = $middleware->handle($request, function ($req) {
        // Simulate low hit rate: 2 hits, 8 misses = 20% hit rate
        Cache::put('cache_performance:hits', 2);
        Cache::put('cache_performance:misses', 8);

        return new Response('Test response');
    });

    expect($response->headers->get('X-Cache-Hit-Rate'))->toBe('20.00%');
    expect($response->headers->get('X-Cache-Hits'))->toBe('2');
    expect($response->headers->get('X-Cache-Misses'))->toBe('8');
});

it('calculates cache hit rate correctly for high performance', function () {
    Cache::put('cache_performance:hits', 0);
    Cache::put('cache_performance:misses', 0);

    $request = Request::create('/test-endpoint', 'GET');
    $middleware = new CachePerformance;

    $response = $middleware->handle($request, function ($req) {
        // Simulate high hit rate: 8 hits, 2 misses = 80% hit rate
        Cache::put('cache_performance:hits', 8);
        Cache::put('cache_performance:misses', 2);

        return new Response('Test response');
    });

    expect($response->headers->get('X-Cache-Hit-Rate'))->toBe('80.00%');
    expect($response->headers->get('X-Cache-Hits'))->toBe('8');
    expect($response->headers->get('X-Cache-Misses'))->toBe('2');
});

it('handles small number of cache requests correctly', function () {
    Cache::put('cache_performance:hits', 0);
    Cache::put('cache_performance:misses', 0);

    $request = Request::create('/test-endpoint', 'GET');
    $middleware = new CachePerformance;

    $response = $middleware->handle($request, function ($req) {
        // Simulate small number of requests: 1 hit, 3 misses = 25% hit rate but only 4 total
        Cache::put('cache_performance:hits', 1);
        Cache::put('cache_performance:misses', 3);

        return new Response('Test response');
    });

    expect($response->headers->get('X-Cache-Hit-Rate'))->toBe('25.00%');
    expect($response->headers->get('X-Cache-Hits'))->toBe('1');
    expect($response->headers->get('X-Cache-Misses'))->toBe('3');
});
