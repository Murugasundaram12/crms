<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleWebSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAuthenticationRoute($request)) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $sessionId = $request->session()->getId();
        $cacheKey = $this->cacheKey((int) Auth::id());
        $activeSessionId = Cache::get($cacheKey);

        if (! $activeSessionId) {
            Cache::put($cacheKey, $sessionId, now()->addMinutes((int) config('session.lifetime', 120)));
            $request->session()->put('validated_web_session', $sessionId);

            return $next($request);
        }

        if ($request->session()->get('validated_web_session') === $sessionId) {
            if (! hash_equals((string) $activeSessionId, (string) $sessionId)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your session expired because this account was logged in on another device.',
                    ], 401);
                }

                return redirect()->route('login')
                    ->with('error', 'Your session expired because this account was logged in on another device.');
            }
        } else {
            Cache::put($cacheKey, $sessionId, now()->addMinutes((int) config('session.lifetime', 120)));
            $request->session()->put('validated_web_session', $sessionId);
        }

        Cache::put($cacheKey, $sessionId, now()->addMinutes((int) config('session.lifetime', 120)));

        return $next($request);
    }

    private function cacheKey(int $userId): string
    {
        return "web_session:user:{$userId}";
    }

    private function isAuthenticationRoute(Request $request): bool
    {
        return $request->routeIs(
            'login',
            'login.form',
            'register',
            'password.*'
        ) || $request->is(
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*'
        );
    }
}
