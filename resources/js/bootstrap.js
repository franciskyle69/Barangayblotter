import axios from "axios";

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.axios.defaults.withCredentials = true;

// Attach Laravel's CSRF token from the blade-rendered <meta> to every
// axios request. Without this, any plain `axios.post(...)` outside of
// Inertia's router (e.g. SystemUpdaterPanel.jsx polling & run-update
// calls) returns 419 "CSRF token mismatch" because the cookie-based
// Inertia flow is bypassed.
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token?.content) {
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else if (import.meta.env.DEV) {
    // eslint-disable-next-line no-console
    console.warn(
        "CSRF token meta tag not found. Non-Inertia axios requests will fail with 419.",
    );
}
