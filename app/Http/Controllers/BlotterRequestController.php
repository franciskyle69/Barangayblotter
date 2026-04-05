<?php

namespace App\Http\Controllers;

use App\Models\BlotterRequest;
use App\Models\Incident;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlotterRequestController extends Controller
{
    private function shouldScopeRequestsToCurrentUser(Request $request): bool
    {
        $tenant = app('current_tenant');

        return $tenant
            ? !$request->user()?->hasTenantPermission($tenant, 'review_blotter_requests')
            : true;
    }

    private function canReviewRequests(Request $request): bool
    {
        $tenant = app('current_tenant');
        $user = $request->user();

        return $tenant && $user
            ? $user->hasTenantPermission($tenant, 'review_blotter_requests')
            : false;
    }

    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $user = $request->user();
        $role = $user->roleIn($tenant);

        // Global scope handles tenant filtering
        $query = BlotterRequest::with(['incident', 'requestedBy', 'reviewedBy']);

        if ($this->shouldScopeRequestsToCurrentUser($request)) {
            $query->where('requested_by_user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to'));
        }

        $requests = $query->latest()->paginate(15);
        return Inertia::render('BlotterRequests/Index', [
            'requests' => $requests,
            'role' => $role,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        // Global scope handles tenant filtering on Incident
        $incidents = Incident::orderBy('incident_date', 'desc')->get();
        return Inertia::render('BlotterRequests/Create', [
            'incidents' => $incidents,
            'initialIncidentId' => $request->query('incident_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('current_tenant');

        $validated = $request->validate([
            'incident_id' => 'required|exists:incidents,id',
            'purpose' => 'nullable|string|max:255',
        ]);
        // Global scope ensures only tenant's incidents are found
        $incident = Incident::findOrFail($validated['incident_id']);

        // tenant_id is auto-set by BelongsToTenant trait
        $blotterRequest = BlotterRequest::create([
            'incident_id' => $incident->id,
            'requested_by_user_id' => $request->user()->id,
            'purpose' => $validated['purpose'] ?? null,
            'status' => BlotterRequest::STATUS_PENDING,
        ]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.blotter_request.create',
            description: 'Submitted a blotter copy request.',
            metadata: [
                'incident_id' => $incident->id,
                'status' => $blotterRequest->status,
            ],
            targetType: 'blotter_request',
            targetId: $blotterRequest->id,
            tenantId: $tenant->id,
        );

        return redirect()->route('blotter-requests.index')->with('success', 'Blotter copy request submitted.');
    }

    public function approve(Request $request, BlotterRequest $blotterRequest): RedirectResponse
    {
        $tenant = app('current_tenant');
        if (!$this->canReviewRequests($request)) {
            abort(403, 'Only barangay admin/staff can approve requests.');
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Global scope ensures only tenant's requests are accessible
        $blotterRequest->update([
            'status' => BlotterRequest::STATUS_APPROVED,
            'admin_user_id' => $request->user()->id,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.blotter_request.approve',
            description: 'Approved a blotter request.',
            metadata: [
                'incident_id' => $blotterRequest->incident_id,
            ],
            targetType: 'blotter_request',
            targetId: $blotterRequest->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', 'Request approved.');
    }

    public function reject(Request $request, BlotterRequest $blotterRequest): RedirectResponse
    {
        $tenant = app('current_tenant');
        if (!$this->canReviewRequests($request)) {
            abort(403, 'Only barangay admin/staff can reject requests.');
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Global scope ensures only tenant's requests are accessible
        $blotterRequest->update([
            'status' => BlotterRequest::STATUS_REJECTED,
            'admin_user_id' => $request->user()->id,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        ActivityLogService::record(
            request: $request,
            action: 'tenant.blotter_request.reject',
            description: 'Rejected a blotter request.',
            metadata: [
                'incident_id' => $blotterRequest->incident_id,
            ],
            targetType: 'blotter_request',
            targetId: $blotterRequest->id,
            tenantId: $tenant->id,
        );

        return back()->with('success', 'Request rejected.');
    }
}
