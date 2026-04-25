<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a user has `must_change_password = true`, funnel ALL requests to
 * the forced-password-change flow (or logout). Previous version only
 * blocked write verbs (POST/PUT/PATCH/DELETE) which let the user freely
 * browse dashboards, incidents, settings etc. via GET — defeating the
 * entire point of forcing a password change on an issued temporary
 * credential.
 */
class EnforcePasswordChange
{
    /**
     * Route names that remain usable even when `must_change_password` is
     * set — the password-change form itself, its submit endpoint, and
     * logout. Anything else is blocked until the flag clears.
     */
    private const ALLOWED_ROUTES = [
        'logout',
        'password.force.change',
        'password.force.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->must_change_password) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if ($request->header('X-Inertia')) {
            // Inertia requests must never receive plain JSON.
            // Force a client-side visit to the password-change page.
            return Inertia::location(route('password.force.change', absolute: false));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You must change your password before continuing.',
                'redirect' => route('password.force.change', absolute: false),
            ], 423);
        }

        // Full-page navigations land on the forced-change form.
        return redirect()
            ->route('password.force.change')
            ->with('warning', 'You must change your password before continuing.');
    }
}
