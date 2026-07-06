<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Behind a reverse proxy (the app is only reachable via it), honour the
        // X-Forwarded-* headers so Laravel sees the real client IP and https
        // scheme — required for correct URLs, secure cookies and rate limiting.
        // TRUSTED_PROXIES: '*' (default) trusts all, or a comma-separated list of IPs/CIDRs.
        $proxies = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(
            at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)),
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
