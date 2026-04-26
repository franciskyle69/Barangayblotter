import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp, config, router } from '@inertiajs/react';

/**
 * Laravel CSRF: every Inertia visit must send tokens that match the session.
 * Reading meta + XSRF cookie on each visit avoids stale tokens (419) on
 * tenant subdomains / long-lived tabs where axios defaults do not apply.
 */
function xsrfTokenFromCookie() {
    if (typeof document === 'undefined') {
        return null;
    }
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (!match?.[1]) {
        return null;
    }
    try {
        return decodeURIComponent(match[1]);
    } catch {
        return match[1];
    }
}

function setCsrfToken(token) {
    if (typeof document === 'undefined') {
        return;
    }
    if (!token) {
        return;
    }

    let meta = document.head?.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute('name', 'csrf-token');
        document.head?.appendChild(meta);
    }
    meta.setAttribute('content', token);

    // Keep axios defaults in sync for any non-Inertia calls.
    if (window?.axios?.defaults?.headers?.common) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
}

config.set('visitOptions', (href, options) => {
    const headers = { ...(options.headers ?? {}) };
    const meta = document.head
        ?.querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    const xsrf = xsrfTokenFromCookie();
    if (meta) {
        headers['X-CSRF-TOKEN'] = meta;
    }
    if (xsrf) {
        headers['X-XSRF-TOKEN'] = xsrf;
    }
    return { ...options, headers };
});

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        const path = `./Pages/${name}.jsx`;
        if (!(path in pages)) throw new Error(`Page not found: ${name}`);
        return pages[path]();
    },
    setup({ el, App, props }) {
        // Ensure meta/axios CSRF matches the current session.
        setCsrfToken(props?.initialPage?.props?.csrf_token);

        // After login/logout, Laravel regenerates the session which changes the CSRF
        // token. Inertia swaps page props without re-rendering the original blade
        // `<meta>` tag, so we update it on every successful navigation.
        router.on('success', (event) => {
            setCsrfToken(event.detail.page.props.csrf_token);
        });

        createRoot(el).render(<App {...props} />);
    },
});
