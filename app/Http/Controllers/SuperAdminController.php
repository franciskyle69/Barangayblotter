<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $tenants = Tenant::with('plan')->withCount('incidents')->get();
        $totalIncidents = Incident::count();
        $incidentsThisMonth = Incident::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $byStatus = Incident::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $recentIncidents = Incident::with(['tenant', 'reportedBy'])->latest()->limit(20)->get();
        return Inertia::render('Super/Dashboard', [
            'tenants' => $tenants,
            'totalIncidents' => $totalIncidents,
            'incidentsThisMonth' => $incidentsThisMonth,
            'byStatus' => $byStatus,
            'recentIncidents' => $recentIncidents,
        ]);
    }

    public function tenants(): Response
    {
        $tenants = Tenant::with('plan')->withCount('incidents')->get();
        return Inertia::render('Super/Tenants', ['tenants' => $tenants]);
    }
}
