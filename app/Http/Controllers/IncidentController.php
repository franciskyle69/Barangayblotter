<?php

namespace App\Http\Controllers;

use App\Mail\IncidentReportedToOfficialsMail;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    private function isScopedIncidentViewer(Request $request, Tenant $tenant): bool
    {
        return !$request->user()?->hasTenantPermission($tenant, 'manage_incidents');
    }

    private function assertCanSubmit(Request $request): void
    {
        $tenant = app('current_tenant');
        if (!$request->user()?->hasTenantPermission($tenant, 'create_incidents')) {
            abort(403, 'You must have a role in this barangay to report an incident.');
        }
    }

    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $query = Incident::with(['reportedBy', 'mediations.mediator']);
        $role = $request->user()->roleIn($tenant);

        if ($this->isScopedIncidentViewer($request, $tenant)) {
            $query->where('reported_by_user_id', $request->user()->id);
        }

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
        return Inertia::render('Incidents/Index', [
            'incidents' => $incidents,
            'statuses' => Incident::statuses(),
            'role' => $role,
        ]);
    }

    public function create(): Response
    {
        $this->assertCanSubmit(request());
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
        $this->assertCanSubmit($request);
        $tenant = app('current_tenant');
        $reporter = $request->user();
        $reporterRole = $reporter->roleIn($tenant);

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

        $this->notifyOfficialsIfResidentOrCitizen($tenant, $incident, $reporter, $reporterRole);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.incident.create',
            description: 'Created an incident report.',
            metadata: [
                'incident_type' => $incident->incident_type,
                'status' => $incident->status,
                'reported_role' => $reporterRole,
            ],
            targetType: 'incident',
            targetId: $incident->id,
            tenantId: $tenant->id,
        );

        return redirect()->route('incidents.show', $incident)->with('success', 'Incident recorded successfully.');
    }

    private function notifyOfficialsIfResidentOrCitizen(Tenant $tenant, Incident $incident, User $reporter, ?string $reporterRole): void
    {
        if (!in_array($reporterRole, [User::ROLE_RESIDENT, User::ROLE_CITIZEN], true)) {
            return;
        }

        $officialRoles = [
            User::ROLE_BARANGAY_ADMIN,
            User::ROLE_PUROK_LEADER,
            User::ROLE_PUROK_SECRETARY,
            User::ROLE_COMMUNITY_WATCH,
            User::ROLE_MEDIATOR,
        ];

        $officialEmails = User::query()
            ->whereHas('tenants', function ($query) use ($tenant, $officialRoles) {
                $query
                    ->where('tenants.id', $tenant->id)
                    ->whereIn('tenant_user.role', $officialRoles);
            })
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($officialEmails->isEmpty()) {
            return;
        }

        try {
            foreach ($officialEmails as $email) {
                Mail::to($email)->send(new IncidentReportedToOfficialsMail($incident, $tenant, $reporter, $reporterRole));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function show(Request $request, Incident $incident): Response|RedirectResponse
    {
        // Global scope ensures only tenant's incidents are accessible
        $incident->load(['attachments', 'mediations.mediator', 'reportedBy']);
        $tenant = app('current_tenant');
        $role = $request->user()->roleIn($tenant);

        if (
            $this->isScopedIncidentViewer($request, $tenant)
            && $incident->reported_by_user_id !== $request->user()->id
        ) {
            abort(403, 'You can only view incidents that you submitted.');
        }

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
        $tenant = app('current_tenant');
        $beforeStatus = $incident->status;

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

        ActivityLogService::record(
            request: $request,
            action: 'tenant.incident.update',
            description: 'Updated an incident report.',
            metadata: [
                'before_status' => $beforeStatus,
                'after_status' => $incident->status,
                'incident_type' => $incident->incident_type,
            ],
            targetType: 'incident',
            targetId: $incident->id,
            tenantId: $tenant->id,
        );

        return redirect()->route('incidents.show', $incident)->with('success', 'Incident updated.');
    }

    public function destroy(Request $request, Incident $incident): RedirectResponse
    {
        // Global scope ensures only tenant's incidents are accessible
        $tenant = app('current_tenant');
        $incidentId = $incident->id;
        $incidentType = $incident->incident_type;

        foreach ($incident->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
        }
        $incident->delete();

        ActivityLogService::record(
            request: $request,
            action: 'tenant.incident.delete',
            description: 'Deleted an incident report.',
            metadata: [
                'incident_type' => $incidentType,
            ],
            targetType: 'incident',
            targetId: $incidentId,
            tenantId: $tenant->id,
        );

        return redirect()->route('incidents.index')->with('success', 'Incident deleted.');
    }
}
