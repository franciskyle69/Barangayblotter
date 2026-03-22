<?php

namespace App\Http\Controllers\Auth;

use App\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSelectController extends Controller
{
    public function show(): Response
    {
        $tenants = request()->user()->tenants()->with('plan')->get();
        return Inertia::render('Auth/TenantSelect', ['tenants' => $tenants]);
    }

    public function select(Request $request): RedirectResponse
    {
        $tenantId = $request->validate(['tenant_id' => 'required|exists:tenants,id'])['tenant_id'];
        $user = $request->user();
        if (!$user->tenants()->where('tenants.id', $tenantId)->exists()) {
            abort(403);
        }
        session(['current_tenant_id' => (int) $tenantId]);
        app()->instance('current_tenant', Tenant::find($tenantId));
        return redirect()->route('dashboard');
    }
}
