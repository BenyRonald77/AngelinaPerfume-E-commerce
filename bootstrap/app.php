<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Di VPS aplikasi berada di belakang Caddy (HTTPS); header X-Forwarded-* dipercaya supaya URL
        // dan aset memakai https. Port aplikasi hanya terbuka untuk Caddy, bukan untuk publik.
        $middleware->trustProxies(at: '*');

        // Webhook Xendit diverifikasi lewat x-callback-token, bukan token CSRF
        $middleware->validateCsrfTokens(except: ['webhooks/xendit']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
