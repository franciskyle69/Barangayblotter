<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_super_admin) {
            return $next($request);
        }
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (!$tenant) {
            return redirect()->route('tenant.select')
                ->with('warning', 'Please select a barangay to continue.');
        }
        $belongs = $request->user()->tenants()->where('tenants.id', $tenant->id)->exists();
        if (!$belongs) {
            abort(403, 'You do not have access to this barangay.');
        }
        return $next($request);
    }
}
