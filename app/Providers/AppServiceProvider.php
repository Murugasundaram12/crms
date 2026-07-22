<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate as GateFacade;
use Illuminate\Support\Facades\View;

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

        View::composer('*', function (): void {
            if (Auth::check()) {
                $user = Auth::user();
                $user->loadMissing('roles.permissions');

                if ($this->isSuperAdmin($user)) {
                    $permissionKeys = \App\Models\Permission::query()
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
                        ->first(fn(string $middleware) => str_starts_with($middleware, 'permission:'));

                    if (! $permissionMiddleware) {
                        continue;
                    }

                    $permissionKey = trim(substr($permissionMiddleware, strlen('permission:')));
                    if ($permissionKey === '') {
                        continue;
                    }

                    $methods = array_values(array_filter(
                        $route->methods(),
                        fn(string $method) => ! in_array(strtoupper($method), ['HEAD'], true)
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
