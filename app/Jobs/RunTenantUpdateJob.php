<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantUpdate;
use App\Services\TenantDatabaseManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunTenantUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(public int $tenantUpdateId) {}

    public function handle(TenantDatabaseManager $tenantDatabases): void
    {
        $update = TenantUpdate::findOrFail($this->tenantUpdateId);

        $update->update([
            'status' => TenantUpdate::STATUS_RUNNING,
            'started_at' => now(),
        ]);
        $update->appendLog('Tenant update started (tenant migrations only).');

        try {
            $tenant = Tenant::query()->findOrFail($update->tenant_id);

            $update->appendLog('Target tenant: ' . ($tenant->name ?? $tenant->id));
            $update->appendLog('Running tenant migrations...');

            $tenantDatabases->migrateTenantDatabase($tenant);

            $update->appendLog('Tenant migrations completed.');
            $update->update([
                'status' => TenantUpdate::STATUS_SUCCESS,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $update->appendLog('ERROR: ' . $e->getMessage());
            $update->update([
                'status' => TenantUpdate::STATUS_FAILED,
                'finished_at' => now(),
            ]);
            throw $e;
        }
    }
}

