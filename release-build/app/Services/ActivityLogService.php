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

        CentralActivityLog::query()->create([
            'user_id' => $user?->id,
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
    }
}
