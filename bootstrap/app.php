<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\PersistBuyerCart;
use App\Http\Middleware\PreventBackHistoryCache;
use App\Http\Middleware\RestoreRememberedLogin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // boombuy.store reaches this app through a Cloudflare Tunnel running on
        // the same machine, so requests arrive from localhost as plain http.
        // Trust the tunnel's X-Forwarded-* headers so links and redirects use
        // https://boombuy.store instead of http:// or localhost.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        $middleware->web(append: [
            RestoreRememberedLogin::class,
            EnsureAccountActive::class,
            // After the login is restored: bring the saved cart back, save changes to it.
            PersistBuyerCart::class,
            PreventBackHistoryCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
