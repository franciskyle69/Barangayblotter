<?php

namespace App\Http\Controllers;

use App\Jobs\RunTenantUpdateJob;
use App\Models\TenantUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantUpdateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $tenant = app('current_tenant');
        $user = $request->user();

        $update = TenantUpdate::create([
            'tenant_id' => $tenant->id,
            'triggered_by_user_id' => $user?->id,
            'status' => TenantUpdate::STATUS_QUEUED,
            'log' => '',
        ]);

        RunTenantUpdateJob::dispatch($update->id);

        return response()->json([
            'id' => $update->id,
            'status' => $update->status,
        ], 202);
    }

    public function show(Request $request, TenantUpdate $tenantUpdate): JsonResponse
    {
        $tenant = app('current_tenant');

        if ((int) $tenantUpdate->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        return response()->json([
            'id' => $tenantUpdate->id,
            'status' => $tenantUpdate->status,
            'log' => $tenantUpdate->log,
            'started_at' => $tenantUpdate->started_at,
            'finished_at' => $tenantUpdate->finished_at,
        ]);
    }
}

