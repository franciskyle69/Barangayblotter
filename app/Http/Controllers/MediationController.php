<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Mediation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MediationController extends Controller
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
            abort(403, 'Only barangay admin/staff can access mediations.');
        }
    }

    public function index(Request $request): Response
    {
        $this->assertAdminOrStaff($request);
        // Global scope on Incident handles tenant filtering
        $incidentsWithMediations = Incident::whereHas('mediations')
            ->with(['mediations.mediator'])
            ->latest()
            ->paginate(15);
        return Inertia::render('Mediations/Index', ['incidentsWithMediations' => $incidentsWithMediations]);
    }

    public function create(Incident $incident): Response|RedirectResponse
    {
        $this->assertAdminOrStaff(request());
        $tenant = app('current_tenant');
        // Global scope ensures $incident belongs to current tenant via route model binding
        if (!$tenant->plan->mediation_scheduling) {
            abort(404);
        }
        $mediators = $tenant->users()->wherePivot('role', User::ROLE_MEDIATOR)->get();
        return Inertia::render('Mediations/Create', [
            'incident' => $incident,
            'mediators' => $mediators,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertAdminOrStaff($request);
        $tenant = app('current_tenant');

        $validated = $request->validate([
            'incident_id' => 'required|exists:incidents,id',
            'mediator_user_id' => [
                'required',
                Rule::exists('tenant_user', 'user_id')->where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->where('role', User::ROLE_MEDIATOR);
                }),
            ],
            'scheduled_at' => 'required|date',
        ]);
        // Global scope ensures only tenant's incidents are found
        $incident = Incident::findOrFail($validated['incident_id']);

        // tenant_id is auto-set by BelongsToTenant trait
        Mediation::create([
            'incident_id' => $incident->id,
            'mediator_user_id' => $validated['mediator_user_id'],
            'scheduled_at' => $validated['scheduled_at'],
        ]);
        $incident->update(['status' => Incident::STATUS_UNDER_MEDIATION]);
        return redirect()->route('incidents.show', $incident)->with('success', 'Mediation scheduled.');
    }

    public function update(Request $request, Mediation $mediation): RedirectResponse
    {
        $this->assertAdminOrStaff($request);
        // Global scope on Mediation ensures it belongs to current tenant
        $validated = $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled,no_show',
            'agreement_notes' => 'nullable|string',
            'settlement_terms' => 'nullable|string',
        ]);
        $mediation->update($validated);
        if ($validated['status'] === 'completed') {
            $mediation->update(['completed_at' => now()]);
            $mediation->incident->update(['status' => Incident::STATUS_SETTLED]);
        }
        return back()->with('success', 'Mediation updated.');
    }
}
