<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class Authenticate extends  Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (FacadesAuth::check()) {
            return $next($request);
        }
        return redirect()->route('login');
    }

}