import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";
import Swal from "sweetalert2";
import ForcedPasswordChangeModal from "../../Components/ForcedPasswordChangeModal";

const Icon = ({ name, className = "size-4" }) => {
    const common = {
        className,
        fill: "none",
        stroke: "currentColor",
        strokeWidth: 2,
        strokeLinecap: "round",
        strokeLinejoin: "round",
        viewBox: "0 0 24 24",
        "aria-hidden": true,
    };

    switch (name) {
        case "dashboard":
            return (
                <svg {...common}>
                    <path d="M3 13h8V3H3v10z" />
                    <path d="M13 21h8V11h-8v10z" />
                    <path d="M13 3h8v6h-8V3z" />
                    <path d="M3 21h8v-6H3v6z" />
                </svg>
            );
        case "tenants":
            return (
                <svg {...common}>
                    <path d="M3 21h18" />
                    <path d="M5 21V9l7-5 7 5v12" />
                    <path d="M9 21v-6h6v6" />
                </svg>
            );
        case "settings":
            return (
                <svg {...common}>
                    <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
                    <path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2 2 0 0 1-1.4 3.4 2 2 0 0 1-1.4-.6l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1.1 1.7V22a2 2 0 0 1-4 0v-.2a1.8 1.8 0 0 0-1.1-1.7 1.8 1.8 0 0 0-2 .4l-.1.1A2 2 0 1 1 2.1 19l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.7-1.1H1a2 2 0 0 1 0-4h-.1a1.8 1.8 0 0 0 1.7-1.1 1.8 1.8 0 0 0-.4-2l-.1-.1A2 2 0 1 1 4.5 4.6l.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1.1-1.7V3a2 2 0 0 1 4 0v.2a1.8 1.8 0 0 0 1.1 1.7 1.8 1.8 0 0 0 2-.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.7 1.1H23a2 2 0 0 1 0 4h-.2a1.8 1.8 0 0 0-1.7 1.1z" />
                </svg>
            );
        case "roles":
            return (
                <svg {...common}>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <path d="M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
                    <path d="M20 8v6" />
                    <path d="M23 11h-6" />
                </svg>
            );
        case "requests":
            return (
                <svg {...common}>
                    <path d="M8 6h13" />
                    <path d="M8 12h13" />
                    <path d="M8 18h13" />
                    <path d="M3 6h.01" />
                    <path d="M3 12h.01" />
                    <path d="M3 18h.01" />
                </svg>
            );
        case "logs":
            return (
                <svg {...common}>
                    <path d="M4 4h16v16H4z" />
                    <path d="M8 8h8" />
                    <path d="M8 12h8" />
                    <path d="M8 16h6" />
                </svg>
            );
        case "backup":
            return (
                <svg {...common}>
                    <path d="M12 2v6" />
                    <path d="M9 5h6" />
                    <path d="M4 14a8 8 0 0 1 16 0v7H4v-7z" />
                    <path d="M8 21v-3" />
                    <path d="M16 21v-3" />
                </svg>
            );
        case "release":
            return (
                <svg {...common}>
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    <path d="M3.27 6.96 12 12.01l8.73-5.05" />
                    <path d="M12 22.08V12" />
                </svg>
            );
        case "support":
            return (
                <svg {...common}>
                    <circle cx="12" cy="12" r="9" />
                    <circle cx="12" cy="12" r="3" />
                    <path d="M4.9 4.9l3.5 3.5" />
                    <path d="M15.6 15.6l3.5 3.5" />
                    <path d="M4.9 19.1l3.5-3.5" />
                    <path d="M15.6 8.4l3.5-3.5" />
                </svg>
            );
        default:
            return null;
    }
};

const centralNavItems = [
    // Daily ops
    { type: "section", label: "Main" },
    {
        type: "link",
        label: "Overview",
        mobileLabel: "Dashboard",
        href: "/super/dashboard",
        icon: "dashboard",
    },
    { type: "section", label: "Operations" },
    {
        type: "link",
        label: "Signup Requests",
        mobileLabel: "Requests",
        href: "/super/tenant-signup-requests",
        icon: "requests",
    },
    {
        type: "link",
        label: "Support",
        mobileLabel: "Support",
        href: "/super/support",
        icon: "support",
    },
    {
        type: "link",
        label: "All Barangays",
        mobileLabel: "Barangays",
        href: "/super/tenants",
        icon: "tenants",
    },

    // Auditing & maintenance
    { type: "section", label: "Maintenance" },
    {
        type: "link",
        label: "Activity Logs",
        mobileLabel: "Logs",
        href: "/super/activity-logs",
        icon: "logs",
    },
    {
        type: "link",
        label: "Backup & Restore",
        mobileLabel: "Backup",
        href: "/super/backup-restore",
        icon: "backup",
    },
    {
        type: "link",
        label: "Releases",
        mobileLabel: "Releases",
        href: "/super/releases",
        icon: "release",
    },

    // Configuration
    { type: "section", label: "Administration" },
    {
        type: "link",
        label: "Roles & Permissions",
        mobileLabel: "Roles",
        href: "/super/roles-permissions",
        icon: "roles",
    },
    {
        type: "link",
        label: "Settings",
        mobileLabel: "Settings",
        href: "/super/settings",
        icon: "settings",
    },
];

export default function CentralLayout({ children }) {
    const page = usePage();
    const { auth, app_name, app_version, flash, logo_url } = page.props;
    const lastFlashRef = useRef({ success: null, error: null, warning: null });
    const user = auth?.user;
    const mustChangePassword = Boolean(user?.must_change_password);
    const storageKey = "ui:centralSidebarCollapsed";
    const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
        if (typeof window === "undefined") return false;
        try {
            return window.localStorage.getItem(storageKey) === "1";
        } catch {
            return false;
        }
    });
    const path =
        page.url ||
        (typeof window !== "undefined" ? window.location.pathname : "");

    const toggleSidebar = () => {
        setSidebarCollapsed((prev) => {
            const next = !prev;
            try {
                window.localStorage.setItem(storageKey, next ? "1" : "0");
            } catch {
                // ignore
            }
            return next;
        });
    };

    const isActive = (href) => {
        if (href === "/super/dashboard") return path === href;
        return path.startsWith(href);
    };

    const sidebarWidthClass = sidebarCollapsed ? "lg:pl-20" : "lg:pl-64";

    const brandLabel = useMemo(() => app_name || "Central Admin", [app_name]);

    useEffect(() => {
        const levels = [
            {
                key: "error",
                title: "Error",
                icon: "error",
                text: flash?.error,
            },
            {
                key: "warning",
                title: "Warning",
                icon: "warning",
                text: flash?.warning,
            },
            {
                key: "success",
                title: "Success",
                icon: "success",
                text: flash?.success,
            },
        ];

        for (const item of levels) {
            if (item.text && item.text !== lastFlashRef.current[item.key]) {
                lastFlashRef.current[item.key] = item.text;
                Swal.fire({
                    title: item.title,
                    text: item.text,
                    icon: item.icon,
                    confirmButtonText: "OK",
                });
                break;
            }
        }
    }, [flash?.success, flash?.error, flash?.warning]);

    return (
        <>
            <div
                className={`flex min-h-screen ${
                    mustChangePassword ? "pointer-events-none select-none" : ""
                }`}
                style={{ backgroundColor: "var(--color-central-bg, #0f172a)" }}
            >
            {/* CENTRAL APP SIDEBAR - Deep Blue Theme */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 hidden flex-col border-r transition-all duration-200 lg:flex ${
                    sidebarCollapsed ? "w-20" : "w-64"
                }`}
                style={{
                    backgroundColor: "var(--color-central-sidebar, #0f172a)",
                    borderColor: "rgba(255, 255, 255, 0.1)",
                }}
            >
                <div
                    className={`flex h-16 shrink-0 items-center gap-2 border-b ${
                        sidebarCollapsed ? "px-4" : "px-6"
                    }`}
                    style={{ borderColor: "rgba(255, 255, 255, 0.1)" }}
                >
                    <img
                        src={logo_url || "/images/logo.png"}
                        alt="Logo"
                        className="h-9 w-9 shrink-0 rounded-devias object-contain"
                        style={{ backgroundColor: "rgba(30, 64, 175, 0.2)" }}
                        onError={(e) => {
                            e.target.style.display = "none";
                            e.target.nextSibling?.classList.remove("hidden");
                        }}
                    />
                    <span
                        className="hidden size-9 items-center justify-center rounded-devias text-sm font-bold text-white"
                        style={{
                            backgroundColor:
                                "var(--color-central-primary, #1e40af)",
                        }}
                        aria-hidden
                    >
                        CA
                    </span>
                    {!sidebarCollapsed && (
                        <span className="truncate text-base font-semibold text-white">
                            {brandLabel}
                        </span>
                    )}
                </div>
                <nav
                    className={`flex-1 space-y-0.5 overflow-y-auto py-4 ${
                        sidebarCollapsed ? "px-2" : "px-3"
                    }`}
                    aria-label="Main"
                >
                    {centralNavItems.map((item, idx) => {
                        if (item.type === "section") {
                            if (sidebarCollapsed) return null;
                            return (
                                <div
                                    key={`section-${item.label}-${idx}`}
                                    className="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/50"
                                >
                                    {item.label}
                                </div>
                            );
                        }

                        return (
                        <div key={item.href} className="relative">
                            <span
                                className={`pointer-events-none absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r transition-all duration-200 ${
                                    isActive(item.href)
                                        ? "bg-cyan-300 opacity-100"
                                        : "bg-transparent opacity-0"
                                }`}
                            />
                        <Link
                            href={item.href}
                            className={`group flex items-center gap-3 rounded-devias px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                                isActive(item.href)
                                    ? "text-white"
                                    : "text-slate-400 hover:text-white"
                            }`}
                            style={{
                                backgroundColor: isActive(item.href)
                                    ? "rgba(30, 64, 175, 0.3)"
                                    : "transparent",
                            }}
                                title={sidebarCollapsed ? item.label : undefined}
                        >
                            <span
                                className={`grid size-8 place-items-center rounded-devias transition-all duration-200 ${
                                    isActive(item.href)
                                        ? "text-cyan-200"
                                        : "text-slate-400 group-hover:text-white"
                                }`}
                                style={{
                                    backgroundColor: isActive(item.href)
                                        ? "rgba(6, 182, 212, 0.12)"
                                        : "rgba(255,255,255,0.04)",
                                }}
                            >
                                <Icon name={item.icon} className="size-4" />
                            </span>
                                {!sidebarCollapsed && (
                                    <span className="transition-transform duration-200 group-hover:translate-x-0.5">
                                        {item.label}
                                    </span>
                                )}
                        </Link>
                        </div>
                        );
                    })}
                </nav>
                {app_version && (
                    <div
                        className={`shrink-0 border-t border-white/5 ${
                            sidebarCollapsed ? "px-2 py-3" : "px-6 py-3"
                        }`}
                    >
                        <span
                            className={`inline-flex items-center gap-1.5 rounded-full bg-white/5 font-medium text-slate-400 ${
                                sidebarCollapsed
                                    ? "px-1.5 py-1 text-[10px]"
                                    : "px-2.5 py-1 text-xs"
                            }`}
                            title={`Running ${app_version}`}
                        >
                            <span
                                className="size-1.5 shrink-0 rounded-full bg-cyan-400/80"
                                aria-hidden
                            />
                            {app_version}
                        </span>
                    </div>
                )}
            </aside>

            <div className={`flex flex-1 flex-col ${sidebarWidthClass}`}>
                {/* CENTRAL APP HEADER - Deep Blue Theme */}
                <header
                    className="sticky top-0 z-30 flex h-16 shrink-0 flex-wrap items-center gap-3 border-b px-4 shadow sm:gap-4 sm:px-6"
                    style={{
                        backgroundColor: "#1e293b",
                        borderColor: "rgba(30, 64, 175, 0.3)",
                    }}
                >
                    <button
                        type="button"
                        onClick={toggleSidebar}
                        className="hidden rounded-devias border px-2.5 py-2 text-sm font-medium transition hover:bg-slate-700/40 lg:inline-flex"
                        style={{
                            borderColor: "rgba(71, 85, 105, 0.6)",
                            backgroundColor: "rgba(30, 41, 59, 0.8)",
                            color: "#cbd5e1",
                        }}
                        title={sidebarCollapsed ? "Expand sidebar" : "Collapse sidebar"}
                    >
                        {sidebarCollapsed ? "»" : "«"}
                    </button>
                    <div className="flex flex-1 flex-wrap items-center gap-1 lg:hidden">
                        {centralNavItems
                            .filter((item) => item.type !== "section")
                            .map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-devias px-2.5 py-1.5 text-sm font-medium transition ${
                                    isActive(item.href)
                                        ? "text-blue-200"
                                        : "text-slate-300 hover:text-blue-200"
                                }`}
                                style={{
                                    backgroundColor: isActive(item.href)
                                        ? "rgba(30, 64, 175, 0.3)"
                                        : "transparent",
                                }}
                            >
                                {item.mobileLabel ?? item.label}
                            </Link>
                        ))}
                    </div>
                    {/* CENTRAL ADMIN BADGE - Blue Theme */}
                    <div
                        className="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                        style={{
                            borderColor: "rgba(6, 182, 212, 0.4)",
                            backgroundColor: "rgba(6, 182, 212, 0.1)",
                            color: "#22d3ee",
                        }}
                    >
                        🏛️ Central Admin
                    </div>
                    <div className="ml-auto flex items-center gap-3">
                        <span className="text-sm text-slate-200">
                            {user?.name}
                        </span>
                        <button
                            type="button"
                            onClick={() => router.post("/logout")}
                            className="rounded-devias border px-3 py-2 text-sm font-medium transition"
                            style={{
                                borderColor: "#475569",
                                backgroundColor: "#1e293b",
                                color: "#cbd5e1",
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.backgroundColor = "#334155";
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.backgroundColor = "#1e293b";
                            }}
                        >
                            Logout
                        </button>
                    </div>
                </header>

                {/* CENTRAL APP MAIN - Blue Tinted Background */}
                <main
                    className="flex-1 p-6"
                    style={{
                        backgroundColor: "var(--color-central-bg, #f0f9ff)",
                    }}
                >
                    {children}
                </main>
            </div>
            </div>
            <ForcedPasswordChangeModal open={mustChangePassword} />
        </>
    );
}
