<?php

namespace App\Http\Controllers;

use App\Models\PatrolLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatrolLogController extends Controller
{
    private function assertAdminOrStaff(Request $request): void
    {
        $tenant = app('current_tenant');
        $role = $request->user()?->roleIn($tenant);
        if (!in_array($role, [
            User::ROLE_PUROK_SECRETARY,
            User::ROLE_PUROK_LEADER,
            User::ROLE_COMMUNITY_WATCH,
            User::ROLE_MEDIATOR,
        ], true)) {
            abort(403, 'Only barangay admin/staff can access patrol logs.');
        }
    }

    public function index(Request $request): Response
    {
        $this->assertAdminOrStaff($request);
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
        $this->assertAdminOrStaff(request());
        return Inertia::render('Patrol/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertAdminOrStaff($request);
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
        PatrolLog::create($validated);
        return redirect()->route('patrol.index')->with('success', 'Patrol log saved.');
    }

    public function edit(PatrolLog $patrol): Response|RedirectResponse
    {
        $this->assertAdminOrStaff(request());
        // Global scope ensures only tenant's patrol logs are accessible
        return Inertia::render('Patrol/Edit', ['patrol' => $patrol]);
    }

    public function update(Request $request, PatrolLog $patrol): RedirectResponse
    {
        $this->assertAdminOrStaff($request);
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
        $patrol->update($validated);
        return redirect()->route('patrol.index')->with('success', 'Patrol log updated.');
    }
}
