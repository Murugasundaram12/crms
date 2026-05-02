<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $exception, $request) {
            if (! $request->expectsJson()) {
                $fallbackUrl = url()->previous();

                if (! $fallbackUrl || $fallbackUrl === $request->fullUrl()) {
                    $fallbackUrl = route('dashboard');
                }

                return redirect()->to($fallbackUrl)
                    ->with('error', 'You do not have permission to access this module.');
            }

            return new SymfonyResponse('Forbidden', 403);
        });
    })->create();
