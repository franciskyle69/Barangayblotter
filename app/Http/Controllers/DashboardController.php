<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\PatrolLog;
use App\Models\BlotterRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $user = $request->user();
        $role = $user->roleIn($tenant);

        // Global scope automatically filters by current tenant
        $canSeeAnalytics = $tenant->plan->analytics_dashboard;

        $stats = [
            'incidents_total' => Incident::count(),
            'incidents_this_month' => Incident::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'open' => Incident::where('status', Incident::STATUS_OPEN)->count(),
            'under_mediation' => Incident::where('status', Incident::STATUS_UNDER_MEDIATION)->count(),
            'settled' => Incident::where('status', Incident::STATUS_SETTLED)->count(),
            'escalated' => Incident::where('status', Incident::STATUS_ESCALATED)->count(),
        ];

        $recentIncidents = Incident::with(['reportedBy', 'mediations.mediator'])
            ->latest()
            ->limit(10)
            ->get();

        $recentPatrols = $tenant->plan->central_monitoring
            ? PatrolLog::with('user')->latest()->limit(5)->get()
            : collect();

        $myBlotterRequests = $user->blotterRequests()
            ->where('tenant_id', $tenant->id)
            ->with('incident')
            ->latest()
            ->limit(5)
            ->get();

        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'plan' => [
                'name' => $tenant->plan->name,
                'mediation_scheduling' => $tenant->plan->mediation_scheduling,
                'incident_limit_per_month' => $tenant->plan->incident_limit_per_month,
                'has_unlimited' => $tenant->plan->hasUnlimitedIncidents(),
            ],
        ];

        return Inertia::render('Dashboard', [
            'tenant' => $tenantData,
            'role' => $role,
            'stats' => $stats,
            'recentIncidents' => $recentIncidents,
            'recentPatrols' => $recentPatrols,
            'canSeeAnalytics' => $canSeeAnalytics,
            'myBlotterRequests' => $myBlotterRequests,
        ]);
    }
}
