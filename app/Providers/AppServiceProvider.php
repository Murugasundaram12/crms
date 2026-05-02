<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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
                Auth::user()->loadMissing('roles.permissions');
            }
        });

        GateFacade::before(function ($user, $ability) {
            if (method_exists($user, 'assignedRoles') && $user->assignedRoles()->contains('name', 'Super Admin')) {
                return true;
            }

            if (($user->role ?? null) === 'Super Admin') {
                return true;
            }

            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });
    }
}
