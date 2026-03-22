<?php

namespace App\Http\Controllers;

use App\Models\BlotterRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlotterRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('current_tenant');
        $user = $request->user();
        $role = $user->roleIn($tenant);

        $query = BlotterRequest::where('tenant_id', $tenant->id)->with(['incident', 'requestedBy']);
        if ($role === 'resident') {
            $query->where('requested_by_user_id', $user->id);
        }
        $requests = $query->latest()->paginate(15);
        return Inertia::render('BlotterRequests/Index', ['requests' => $requests, 'role' => $role]);
    }

    public function create(Request $request): Response
    {
        $tenant = app('current_tenant');
        $incidents = $tenant->incidents()->orderBy('incident_date', 'desc')->get();
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
        $incident = Incident::findOrFail($validated['incident_id']);
        if ($incident->tenant_id !== $tenant->id) {
            abort(404);
        }
        BlotterRequest::create([
            'tenant_id' => $tenant->id,
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
        if ($blotterRequest->tenant_id !== $tenant->id) {
            abort(404);
        }
        $blotterRequest->update(['status' => BlotterRequest::STATUS_APPROVED]);
        return back()->with('success', 'Request approved.');
    }

    public function reject(BlotterRequest $blotterRequest): RedirectResponse
    {
        $tenant = app('current_tenant');
        if ($blotterRequest->tenant_id !== $tenant->id) {
            abort(404);
        }
        $blotterRequest->update(['status' => BlotterRequest::STATUS_REJECTED]);
        return back()->with('success', 'Request rejected.');
    }
}
