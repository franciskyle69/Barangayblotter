<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces that an authenticated session is bound to the tenant it was
 * issued for. Defends against cross-tenant session replay.
 *
 * Attack model (pre-fix):
 *   1. User U1 logs in on `tenant-a.example.com`. Session issued, cookie
 *      stored. Session data includes only the Laravel-provided user ID.
 *   2. Attacker gets hold of that cookie (shared cookie domain, XSS,
 *      compromised proxy, etc.) and replays it against
 *      `tenant-b.example.com`.
 *   3. Tenant B's DB happens to contain a different User with the same
 *      numeric ID (user IDs are not globally unique — each tenant DB
 *      has its own autoincrement). Laravel's session guard hydrates
 *      that user without any tenant-awareness checks.
 *   4. Attacker is now authenticated as a different user in a different
 *      tenant, with that user's permissions.
 *
 * Defense (this middleware):
 *   - At login time, LoginController writes `auth_tenant_id` into the
 *     session (null for super admins).
 *   - On every request, this middleware compares that value against the
 *     tenant the request is currently resolving to (`current_tenant` if
 *     bound, null otherwise).
 *   - Any mismatch → Auth::logout() + session invalidation + redirect
 *     to the login page. No chance to serve a single response with a
 *     wrongly-scoped identity.
 *
 * This middleware must run AFTER `SetTenantFromSession` and
 * `SwitchTenantDatabase` so `current_tenant` is bound and the tenant DB
 * connection is configured by the time Auth::logout() re-queries the user.
 */
class VerifyTenantSessionBinding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $sessionAuthTenantId = $request->session()->get('auth_tenant_id', false);

        // Legacy sessions (created before this middleware existed) don't
        // have the binding at all. We treat that as "not yet bound" rather
        // than "mismatched" — sensible downgrade for existing users — but
        // we still force a logout so they re-authenticate and pick up a
        // properly-bound session on the next login.
        if ($sessionAuthTenantId === false) {
            return $this->forceLogout($request, 'missing_auth_tenant_binding');
        }

        $resolvedTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $expected = $sessionAuthTenantId === null ? null : (int) $sessionAuthTenantId;
        $actual = $resolvedTenant?->id;

        if ($user->is_super_admin) {
            // Super admin sessions are valid only on the central domain
            // (no tenant resolved). If a super admin's cookie ends up on
            // a tenant subdomain, log them out — privilege bleed across
            // contexts is dangerous even for legitimate super admins.
            if ($actual !== null) {
                return $this->forceLogout($request, 'super_admin_on_tenant_domain');
            }

            return $next($request);
        }

        if ($expected === null || $actual === null || $expected !== $actual) {
            return $this->forceLogout($request, 'tenant_session_mismatch');
        }

        return $next($request);
    }

    private function forceLogout(Request $request, string $reason): Response
    {
        $user = $request->user();

        ActivityLogService::record(
            request: $request,
            action: 'auth.session_binding_rejected',
            description: 'Session rejected: tenant binding mismatch.',
            metadata: [
                'reason' => $reason,
                'email' => $user?->email,
            ],
            targetType: 'user',
            targetId: $user?->id,
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your session is no longer valid. Please sign in again.',
            ], 401);
        }

        return redirect()->route('login')->with('error', 'Your session is no longer valid. Please sign in again.');
    }
}
