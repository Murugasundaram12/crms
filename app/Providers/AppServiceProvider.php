<?php

namespace App\Providers;

use App\Models\Permission;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate as GateFacade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        RateLimiter::for('web-auth', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('mobile-auth', function (Request $request) {
            $email = strtolower((string) $request->input('email'));
            $deviceId = (string) (
                $request->input('device_id')
                ?? $request->input('deviceId')
                ?? $request->input('device_uid')
                ?? $request->input('deviceUid')
                ?? ''
            );

            return Limit::perMinute(10)->by($email.'|'.$deviceId.'|'.$request->ip());
        });

        RateLimiter::for('tracking-ingest', function (Request $request) {
            $token = $request->bearerToken();
            $deviceId = (string) (
                $request->input('device_id')
                ?? $request->input('deviceId')
                ?? $request->input('device_uid')
                ?? $request->input('deviceUid')
                ?? ''
            );

            return Limit::perMinute(240)->by(($token ? hash('sha256', $token) : $request->ip()).'|'.$deviceId);
        });

        View::composer('*', function (): void {
            if (Auth::check()) {
                $user = Auth::user();
                $user->loadMissing('roles.permissions');

                if ($this->isSuperAdmin($user)) {
                    $permissionKeys = Permission::query()
                        ->whereNotNull('key')
                        ->pluck('key')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                } else {
                    $permissionKeys = method_exists($user, 'effectivePermissionKeys')
                        ? $user->effectivePermissionKeys()
                        : [];
                }

                $permissionRoutes = [];
                foreach (Route::getRoutes() as $route) {
                    $permissionMiddleware = collect($route->gatherMiddleware())
                        ->first(fn (string $middleware) => str_starts_with($middleware, 'permission:'));

                    if (! $permissionMiddleware) {
                        continue;
                    }

                    $permissionKey = trim(substr($permissionMiddleware, strlen('permission:')));
                    if ($permissionKey === '') {
                        continue;
                    }

                    $methods = array_values(array_filter(
                        $route->methods(),
                        fn (string $method) => ! in_array(strtoupper($method), ['HEAD'], true)
                    ));

                    $permissionRoutes[] = [
                        'uri' => trim($route->uri(), '/'),
                        'methods' => $methods,
                        'permission' => $permissionKey,
                    ];
                }

                View::share('permissionUiContext', [
                    'userPermissions' => $permissionKeys,
                    'permissionRoutes' => $permissionRoutes,
                ]);
            }
        });

        GateFacade::before(function ($user, $ability) {
            if ($this->isSuperAdmin($user)) {
                return true;
            }

            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }

    private function isSuperAdmin($user): bool
    {
        if (($user->role ?? null) === 'Super Admin') {
            return true;
        }

        return method_exists($user, 'assignedRoles')
            && $user->assignedRoles()->contains('name', 'Super Admin');
    }
}
