<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    private function assertCitizenCanSubmit(Request $request): void
    {
        $tenant = app('current_tenant');
        $role = $request->user()?->roleIn($tenant);
        if (!in_array($role, [User::ROLE_CITIZEN, User::ROLE_RESIDENT], true)) {
            abort(403, 'Only citizens can submit new incidents.');
        }
    }

    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $query = Incident::with(['reportedBy', 'mediations.mediator']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('incident_type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('incident_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('incident_date', '<=', $request->to);
        }

        $incidents = $query->latest('incident_date')->paginate(15);
        $role = $request->user()->roleIn($tenant);
        return Inertia::render('Incidents/Index', [
            'incidents' => $incidents,
            'statuses' => Incident::statuses(),
            'role' => $role,
        ]);
    }

    public function create(): Response
    {
        $this->assertCitizenCanSubmit(request());
        $tenant = app('current_tenant');
        if (!$tenant->canAddIncident()) {
            abort(403, 'Your plan has reached the monthly incident limit.');
        }
        return Inertia::render('Incidents/Create', [
            'statuses' => Incident::statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCitizenCanSubmit($request);
        $tenant = app('current_tenant');
        if (!$tenant->canAddIncident()) {
            return back()->with('error', 'Monthly incident limit reached.');
        }

        $validated = $request->validate([
            'incident_type' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'incident_date' => 'required|date',
            'complainant_name' => 'required|string|max:255',
            'complainant_contact' => 'nullable|string|max:50',
            'complainant_address' => 'nullable|string',
            'respondent_name' => 'required|string|max:255',
            'respondent_contact' => 'nullable|string|max:50',
            'respondent_address' => 'nullable|string',
            'status' => 'required|in:open,under_mediation,settled,escalated_to_barangay',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        // tenant_id is auto-set by BelongsToTenant trait
        $validated['reported_by_user_id'] = $request->user()->id;
        $validated['submitted_online'] = false;

        $incident = Incident::create($validated);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("incidents/{$incident->id}", 'public');
                IncidentAttachment::create([
                    'incident_id' => $incident->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('incidents.show', $incident)->with('success', 'Incident recorded successfully.');
    }

    public function show(Request $request, Incident $incident): Response|RedirectResponse
    {
        // Global scope ensures only tenant's incidents are accessible
        $incident->load(['attachments', 'mediations.mediator', 'reportedBy']);
        $tenant = app('current_tenant');
        $role = $request->user()->roleIn($tenant);
        return Inertia::render('Incidents/Show', ['incident' => $incident, 'role' => $role]);
    }

    public function edit(Incident $incident): Response|RedirectResponse
    {
        // Global scope ensures only tenant's incidents are accessible
        return Inertia::render('Incidents/Edit', [
            'incident' => $incident,
            'statuses' => Incident::statuses(),
        ]);
    }

    public function update(Request $request, Incident $incident): RedirectResponse
    {
        // Global scope ensures only tenant's incidents are accessible
        $validated = $request->validate([
            'incident_type' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'nullable|string|max:255',
            'incident_date' => 'required|date',
            'complainant_name' => 'required|string|max:255',
            'complainant_contact' => 'nullable|string|max:50',
            'complainant_address' => 'nullable|string',
            'respondent_name' => 'required|string|max:255',
            'respondent_contact' => 'nullable|string|max:50',
            'respondent_address' => 'nullable|string',
            'status' => 'required|in:open,under_mediation,settled,escalated_to_barangay',
        ]);

        $incident->update($validated);
        return redirect()->route('incidents.show', $incident)->with('success', 'Incident updated.');
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        // Global scope ensures only tenant's incidents are accessible
        foreach ($incident->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
        }
        $incident->delete();
        return redirect()->route('incidents.index')->with('success', 'Incident deleted.');
    }
}
