<?php

namespace App\Http\Controllers;

use App\Jobs\RunSystemUpdateJob;
use App\Models\SystemUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemUpdateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $centralConnection = (string) config('tenancy.central_connection', 'central');

        // If the central DB hasn't been migrated yet, creating the log row will fail.
        // This keeps the updater usable on fresh installs without requiring a separate manual step.
        if (!Schema::connection($centralConnection)->hasTable('system_updates')) {
            Artisan::call('migrate', [
                '--database' => $centralConnection,
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }

        $update = SystemUpdate::create([
            'status' => SystemUpdate::STATUS_QUEUED,
            'version' => null,
            'log' => '',
            'maintenance_bypass_secret' => Str::random(48),
        ]);

        RunSystemUpdateJob::dispatch($update->id);

        return response()->json([
            'id' => $update->id,
            'status' => $update->status,
            'maintenance_bypass_url' => $update->maintenanceBypassUrl(),
        ], 202);
    }

    public function show(SystemUpdate $systemUpdate): JsonResponse
    {
        return response()->json([
            'id' => $systemUpdate->id,
            'version' => $systemUpdate->version,
            'status' => $systemUpdate->status,
            'log' => $systemUpdate->log,
            'maintenance_bypass_url' => $systemUpdate->maintenanceBypassUrl(),
            'created_at' => $systemUpdate->created_at,
            'updated_at' => $systemUpdate->updated_at,
        ]);
    }
}

