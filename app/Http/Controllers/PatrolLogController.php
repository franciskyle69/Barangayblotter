<?php

namespace App\Http\Controllers;

use App\Models\PatrolLog;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatrolLogController extends Controller
{
    private function assertCanManagePatrolLogs(Request $request): void
    {
        $tenant = app('current_tenant');
        if (!$request->user()?->hasTenantPermission($tenant, 'manage_patrol_logs')) {
            abort(403, 'You do not have permission to access patrol logs.');
        }
    }

    public function index(Request $request): Response
    {
        $this->assertCanManagePatrolLogs($request);
        // Global scope handles tenant filtering
        $query = PatrolLog::with('user');
        if ($request->filled('date')) {
            $query->whereDate('patrol_date', $request->date);
        }
        $patrolLogs = $query->latest('patrol_date')->paginate(15);
        return Inertia::render('Patrol/Index', ['patrolLogs' => $patrolLogs]);
    }

    public function create(): Response
    {
        $this->assertCanManagePatrolLogs(request());
        return Inertia::render('Patrol/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCanManagePatrolLogs($request);
        $tenant = app('current_tenant');

        $validated = $request->validate([
            'patrol_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'area_patrolled' => 'nullable|string|max:255',
            'activities' => 'nullable|string',
            'incidents_observed' => 'nullable|string',
            'response_details' => 'nullable|string',
            'response_time_minutes' => 'nullable|integer|min:0',
        ]);
        // tenant_id is auto-set by BelongsToTenant trait
        $validated['user_id'] = $request->user()->id;

        $patrolLog = PatrolLog::create($validated);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.patrol.create',
            description: 'Created a patrol log entry.',
            metadata: [
                'patrol_date' => $patrolLog->patrol_date,
                'area_patrolled' => $patrolLog->area_patrolled,
            ],
            targetType: 'patrol_log',
            targetId: $patrolLog->id,
            tenantId: $tenant->id,
        );

        return redirect()->route('patrol.index')->with('success', 'Patrol log saved.');
    }

    public function edit(PatrolLog $patrol): Response|RedirectResponse
    {
        $this->assertCanManagePatrolLogs(request());
        // Global scope ensures only tenant's patrol logs are accessible
        return Inertia::render('Patrol/Edit', ['patrol' => $patrol]);
    }

    public function update(Request $request, PatrolLog $patrol): RedirectResponse
    {
        $this->assertCanManagePatrolLogs($request);
        $tenant = app('current_tenant');

        // Global scope ensures only tenant's patrol logs are accessible
        $validated = $request->validate([
            'patrol_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'area_patrolled' => 'nullable|string|max:255',
            'activities' => 'nullable|string',
            'incidents_observed' => 'nullable|string',
            'response_details' => 'nullable|string',
            'response_time_minutes' => 'nullable|integer|min:0',
        ]);

        $beforeDate = $patrol->patrol_date;
        $patrol->update($validated);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.patrol.update',
            description: 'Updated a patrol log entry.',
            metadata: [
                'before_patrol_date' => $beforeDate,
                'after_patrol_date' => $patrol->patrol_date,
            ],
            targetType: 'patrol_log',
            targetId: $patrol->id,
            tenantId: $tenant->id,
        );

        return redirect()->route('patrol.index')->with('success', 'Patrol log updated.');
    }
}
