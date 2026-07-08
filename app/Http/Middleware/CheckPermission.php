<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (
            ! $user ||
            (
                ! $this->isSuperAdmin($user) &&
                ! $user->hasPermission($permission)
            )
        ) {
            $fallbackUrl = url()->previous();

            if (! $fallbackUrl || $fallbackUrl === $request->fullUrl()) {
                $fallbackUrl = route('dashboard');
            }

            return redirect()->to($fallbackUrl)
                ->with('error', 'You do not have permission to access this module.');
        }

        return $next($request);
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
