<?php

use App\Http\Middleware\CachePerformance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can handle a request', function () {
    $middleware = new CachePerformance();
    $request = Request::create('/test');
    
    $response = $middleware->handle($request, function ($request) {
        return new Response('test');
    });
    
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toBe('test');
});

it('adds cache performance headers when cache activity occurs', function () {
    Cache::put('cache_performance:hits', 5);
    Cache::put('cache_performance:misses', 2);
    
    $middleware = new CachePerformance();
    $request = Request::create('/test');
    
    $response = $middleware->handle($request, function ($request) {
        // Simulate some cache activity
        Cache::put('cache_performance:hits', 6);
        Cache::put('cache_performance:misses', 3);
        return new Response('test');
    });
    
    expect($response->headers->get('X-Cache-Hits'))->toBe('1');
    expect($response->headers->get('X-Cache-Misses'))->toBe('1');
    expect($response->headers->get('X-Cache-Hit-Rate'))->toBe('50.00%');
});

it('stores cache start values in request attributes', function () {
    Cache::put('cache_performance:hits', 10);
    Cache::put('cache_performance:misses', 5);
    
    $middleware = new CachePerformance();
    $request = Request::create('/test');
    
    $middleware->handle($request, function ($request) {
        expect($request->attributes->get('cache_hits_start'))->toBe(10);
        expect($request->attributes->get('cache_misses_start'))->toBe(5);
        return new Response('test');
    });
});