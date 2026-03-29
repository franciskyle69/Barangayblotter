<?php

namespace App\Http\Controllers;

use App\Models\CentralActivityLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SuperActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'action' => trim((string) $request->input('action', '')),
            'tenant_id' => $request->filled('tenant_id') ? (string) $request->input('tenant_id') : '',
        ];

        $emptyLogs = [
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'from' => 0,
            'to' => 0,
            'total' => 0,
            'prev_page_url' => null,
            'next_page_url' => null,
        ];

        $tenants = Tenant::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn(Tenant $tenant) => [
                'id' => (string) $tenant->id,
                'name' => $tenant->name,
            ])
            ->values();

        $setupCommand = 'php artisan migrate --path=database/migrations/2026_03_29_000001_create_central_activity_logs_table.php --force';

        try {
            if (!Schema::connection('central')->hasTable('central_activity_logs')) {
                return Inertia::render('Super/ActivityLogs', [
                    'logs' => $emptyLogs,
                    'filters' => $filters,
                    'actions' => [],
                    'tenants' => $tenants,
                    'setupRequired' => true,
                    'setupMessage' => 'Activity log table is not created yet.',
                    'setupCommand' => $setupCommand,
                ]);
            }
        } catch (Throwable $e) {
            return Inertia::render('Super/ActivityLogs', [
                'logs' => $emptyLogs,
                'filters' => $filters,
                'actions' => [],
                'tenants' => $tenants,
                'setupRequired' => true,
                'setupMessage' => 'Unable to access activity logs table. Please verify database connectivity and run migration.',
                'setupCommand' => $setupCommand,
                'setupError' => $e->getMessage(),
            ]);
        }

        $query = CentralActivityLog::query()->with('user')->latest();

        if ($filters['action'] !== '') {
            $query->where('action', $filters['action']);
        }

        if ($filters['tenant_id'] !== '') {
            $query->where('tenant_id', (int) $filters['tenant_id']);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('actor_email', 'like', "%{$search}%")
                    ->orWhere('target_type', 'like', "%{$search}%")
                    ->orWhere('target_id', 'like', "%{$search}%");
            });
        }

        $logs = $query
            ->paginate(30)
            ->withQueryString()
            ->through(function (CentralActivityLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'actor_name' => $log->actor_name,
                    'actor_email' => $log->actor_email,
                    'target_type' => $log->target_type,
                    'target_id' => $log->target_id,
                    'tenant_id' => $log->tenant_id,
                    'ip_address' => $log->ip_address,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->toIso8601String(),
                    'created_at_human' => $log->created_at?->format('M d, Y h:i:s A'),
                ];
            });

        $actions = CentralActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        return Inertia::render('Super/ActivityLogs', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $actions,
            'tenants' => $tenants,
            'setupRequired' => false,
            'setupMessage' => null,
            'setupCommand' => $setupCommand,
            'setupError' => null,
        ]);
    }
}
