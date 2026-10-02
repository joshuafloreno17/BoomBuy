<?php

use App\Http\Middleware\AutoReceiveDeliveredOrders;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\PersistBuyerCart;
use App\Http\Middleware\PreventBackHistoryCache;
use App\Http\Middleware\RestoreRememberedLogin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            AutoReceiveDeliveredOrders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A form sent from a page left open too long (expired CSRF token):
        // go back to that page and say so, instead of a bare "419 Page Expired".
        // What was typed comes back too, except passwords.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()->back(fallback: url('/'))
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'new_password', 'new_password_confirmation']))
                ->with('bb_notice', 'Your session timed out, so that didn’t go through. Please try again.');
        });
    })->create();
