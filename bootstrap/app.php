<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
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
        $middleware->web(append: [SetLocale::class]);   // FR-05
        $middleware->alias(['role' => EnsureUserHasRole::class]); // FR-03
        // Webhook gateway dipanggil server ke server, diamankan dengan signature HMAC, bukan token CSRF
        $middleware->validateCsrfTokens(except: ['payments/mockpay/callback']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Pelanggaran aturan bisnis: kembali ke halaman sebelumnya dengan pesan yang ramah (Heuristik #9)
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->userMessage()], 422);
            }

            return back()->withInput()->with('error', $e->userMessage());
        });
        $exceptions->dontReport(BusinessRuleException::class);
    })->create();
