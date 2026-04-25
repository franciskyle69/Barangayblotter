<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;

        if ($request->user()?->is_super_admin) {
            // Path-based to avoid APP_URL host mismatches.
            return redirect('/super/dashboard');
        }

        if (!$tenant) {
            return redirect('/login');
        }

        if (!$tenant->is_active) {
            abort(403, 'This barangay account has been deactivated.');
        }

        return $next($request);
    }
}
