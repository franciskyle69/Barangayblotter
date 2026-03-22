<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Mediation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediationController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $incidentsWithMediations = $tenant->incidents()
            ->whereHas('mediations')
            ->with(['mediations.mediator'])
            ->latest()
            ->paginate(15);
        return Inertia::render('Mediations/Index', ['incidentsWithMediations' => $incidentsWithMediations]);
    }

    public function create(Incident $incident): Response|RedirectResponse
    {
        $tenant = app('current_tenant');
        if ($incident->tenant_id !== $tenant->id || !$tenant->plan->mediation_scheduling) {
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
        $tenant = app('current_tenant');
        $validated = $request->validate([
            'incident_id' => 'required|exists:incidents,id',
            'mediator_user_id' => 'required|exists:users,id',
            'scheduled_at' => 'required|date',
        ]);
        $incident = Incident::findOrFail($validated['incident_id']);
        if ($incident->tenant_id !== $tenant->id) {
            abort(404);
        }
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
        $tenant = app('current_tenant');
        if ($mediation->incident->tenant_id !== $tenant->id) {
            abort(404);
        }
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
