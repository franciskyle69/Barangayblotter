<?php

namespace App\Services;

use App\Models\CentralActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogService
{
    public static function record(
        ?Request $request,
        string $action,
        string $description,
        array $metadata = [],
        ?string $targetType = null,
        int|string|null $targetId = null,
        ?int $tenantId = null,
        ?User $actor = null
    ): void {
        try {
            app(self::class)->write(
                request: $request,
                action: $action,
                description: $description,
                metadata: $metadata,
                targetType: $targetType,
                targetId: $targetId,
                tenantId: $tenantId,
                actor: $actor,
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function write(
        ?Request $request,
        string $action,
        string $description,
        array $metadata = [],
        ?string $targetType = null,
        int|string|null $targetId = null,
        ?int $tenantId = null,
        ?User $actor = null
    ): void {
        $user = $actor;

        if (!$user && $request?->user() instanceof User) {
            $user = $request->user();
        }

        // CentralActivityLog is stored on the `central` DB connection and has
        // a FK to `central.users`. Tenant users live in tenant DBs, so their
        // numeric IDs are not guaranteed to exist in central.users.
        //
        // To avoid breaking critical flows (like tenant login) we only set
        // user_id when we are sure the actor ID exists in central.users.
        $centralUserId = null;
        $tenantUserId = null;
        if ($user?->is_super_admin) {
            $centralUserId = $user->id;
        } else {
            $tenantUserId = $user?->id;
        }

        try {
            CentralActivityLog::query()->create([
                'user_id' => $centralUserId,
                'tenant_user_id' => $tenantUserId,
                'actor_name' => $user?->name,
                'actor_email' => $user?->email,
                'action' => $action,
                'description' => $description,
                'target_type' => $targetType,
                'target_id' => $targetId !== null ? (string) $targetId : null,
                'tenant_id' => $tenantId,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'metadata' => $metadata,
            ]);
        } catch (Throwable $e) {
            // Logging must never break authentication or other core flows.
            report($e);
        }
    }
}
