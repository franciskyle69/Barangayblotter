<?php

namespace App\Http\Controllers;

use App\Models\BlotterRequest;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlotterRequestController extends Controller
{
    private function isCitizenLike(?string $role): bool
    {
        return in_array($role, [User::ROLE_RESIDENT, User::ROLE_CITIZEN], true);
    }

    private function canReviewRequests(?string $role): bool
    {
        return in_array($role, [
            User::ROLE_PUROK_SECRETARY,
            User::ROLE_PUROK_LEADER,
            User::ROLE_COMMUNITY_WATCH,
        ], true);
    }

    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $user = $request->user();
        $role = $user->roleIn($tenant);

        // Global scope handles tenant filtering
        $query = BlotterRequest::with(['incident', 'requestedBy', 'reviewedBy']);

        if ($this->isCitizenLike($role)) {
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
        $validated = $request->validate([
            'incident_id' => 'required|exists:incidents,id',
            'purpose' => 'nullable|string|max:255',
        ]);
        // Global scope ensures only tenant's incidents are found
        $incident = Incident::findOrFail($validated['incident_id']);

        // tenant_id is auto-set by BelongsToTenant trait
        BlotterRequest::create([
            'incident_id' => $incident->id,
            'requested_by_user_id' => $request->user()->id,
            'purpose' => $validated['purpose'] ?? null,
            'status' => BlotterRequest::STATUS_PENDING,
        ]);
        return redirect()->route('blotter-requests.index')->with('success', 'Blotter copy request submitted.');
    }

    public function approve(BlotterRequest $blotterRequest): RedirectResponse
    {
        $tenant = app('current_tenant');
        $role = request()->user()?->roleIn($tenant);
        if (!$this->canReviewRequests($role)) {
            abort(403, 'Only barangay admin/staff can approve requests.');
        }

        $validated = request()->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Global scope ensures only tenant's requests are accessible
        $blotterRequest->update([
            'status' => BlotterRequest::STATUS_APPROVED,
            'admin_user_id' => request()->user()->id,
            'remarks' => $validated['remarks'] ?? null,
        ]);
        return back()->with('success', 'Request approved.');
    }

    public function reject(BlotterRequest $blotterRequest): RedirectResponse
    {
        $tenant = app('current_tenant');
        $role = request()->user()?->roleIn($tenant);
        if (!$this->canReviewRequests($role)) {
            abort(403, 'Only barangay admin/staff can reject requests.');
        }

        $validated = request()->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Global scope ensures only tenant's requests are accessible
        $blotterRequest->update([
            'status' => BlotterRequest::STATUS_REJECTED,
            'admin_user_id' => request()->user()->id,
            'remarks' => $validated['remarks'] ?? null,
        ]);
        return back()->with('success', 'Request rejected.');
    }
}
