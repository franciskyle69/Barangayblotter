<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\PatrolLog;
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

        $incidentsQuery = $tenant->incidents();
        $canSeeAnalytics = $tenant->plan->analytics_dashboard;

        $stats = [
            'incidents_total' => (clone $incidentsQuery)->count(),
            'incidents_this_month' => (clone $incidentsQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'open' => (clone $incidentsQuery)->where('status', Incident::STATUS_OPEN)->count(),
            'under_mediation' => (clone $incidentsQuery)->where('status', Incident::STATUS_UNDER_MEDIATION)->count(),
            'settled' => (clone $incidentsQuery)->where('status', Incident::STATUS_SETTLED)->count(),
            'escalated' => (clone $incidentsQuery)->where('status', Incident::STATUS_ESCALATED)->count(),
        ];

        $recentIncidents = $tenant->incidents()
            ->with(['reportedBy', 'mediations.mediator'])
            ->latest()
            ->limit(10)
            ->get();

        $recentPatrols = $tenant->plan->central_monitoring
            ? $tenant->patrolLogs()->with('user')->latest()->limit(5)->get()
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
