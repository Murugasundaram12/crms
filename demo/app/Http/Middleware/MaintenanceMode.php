<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Settings;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next)
    {
        $maintenance = Settings::where('key', 'is_maintenance')->first();
        $user = Auth::user();

        if ($maintenance && (int) $maintenance->value === 1 && $user->email != 'superadmin@gmail.com') {
            return redirect()->route('maintenance');
        }

        // ✅ Not in maintenance mode
        return $next($request);
    }
}
