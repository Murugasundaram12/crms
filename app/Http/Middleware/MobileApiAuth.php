<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $apiToken = MobileApiToken::query()
            ->with('user.roles.permissions')
            ->where('token_hash', hash('sha256', $plainToken))
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $apiToken || ! $apiToken->user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $apiToken->user);

        return $next($request);
    }
}
