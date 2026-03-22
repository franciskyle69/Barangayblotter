<?php

namespace App\Http\Controllers;

use App\Models\PatrolLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatrolLogController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $query = $tenant->patrolLogs()->with('user');
        if ($request->filled('date')) {
            $query->whereDate('patrol_date', $request->date);
        }
        $patrolLogs = $query->latest('patrol_date')->paginate(15);
        return Inertia::render('Patrol/Index', ['patrolLogs' => $patrolLogs]);
    }

    public function create(): Response
    {
        return Inertia::render('Patrol/Create');
    }

    public function store(Request $request): RedirectResponse
    {
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
        $validated['tenant_id'] = $tenant->id;
        $validated['user_id'] = $request->user()->id;
        PatrolLog::create($validated);
        return redirect()->route('patrol.index')->with('success', 'Patrol log saved.');
    }

    public function edit(PatrolLog $patrol): Response|RedirectResponse
    {
        $tenant = app('current_tenant');
        if ($patrol->tenant_id !== $tenant->id) {
            abort(404);
        }
        return Inertia::render('Patrol/Edit', ['patrol' => $patrol]);
    }

    public function update(Request $request, PatrolLog $patrol): RedirectResponse
    {
        $tenant = app('current_tenant');
        if ($patrol->tenant_id !== $tenant->id) {
            abort(404);
        }
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
