import { router } from "@inertiajs/react";

/**
 * POST /logout with explicit CSRF — avoids 419 when cookie/header sync breaks
 * on tenant subdomains or after long idle sessions.
 */
export function postLogout() {
    const csrfToken =
        typeof document !== "undefined"
            ? document.head
                  ?.querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content")
            : null;

    router.post(
        "/logout",
        csrfToken ? { _token: csrfToken } : {},
        {
            headers: csrfToken ? { "X-CSRF-TOKEN": csrfToken } : undefined,
        },
    );
}
