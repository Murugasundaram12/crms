<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'mobile.api' => \App\Http\Middleware\MobileApiAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $modelNotFoundMessage = function (ModelNotFoundException $exception, $request): string {
            if ($request->is('api/employees/*') || $request->is('api/admin/employees/*')) {
                return 'Employee not found.';
            }

            $model = class_basename($exception->getModel() ?: 'Resource');

            return "{$model} not found.";
        };

        $exceptions->render(function (ModelNotFoundException $exception, $request) use ($modelNotFoundMessage) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $modelNotFoundMessage($exception, $request),
            ], 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, $request) use ($modelNotFoundMessage) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $previous = $exception->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                return response()->json([
                    'message' => $modelNotFoundMessage($previous, $request),
                ], 404);
            }

            return null;
        });

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
