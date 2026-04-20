import { Link, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
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
        case "incidents":
            return (
                <svg {...common}>
                    <path d="M4 4h16v16H4z" />
                    <path d="M8 9h8" />
                    <path d="M8 13h8" />
                    <path d="M8 17h5" />
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
        case "users":
            return (
                <svg {...common}>
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <path d="M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
                </svg>
            );
        case "roles":
            return (
                <svg {...common}>
                    <path d="M20 8v6" />
                    <path d="M23 11h-6" />
                    <path d="M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
                    <path d="M2 21v-2a4 4 0 0 1 4-4h4" />
                </svg>
            );
        case "branding":
            return (
                <svg {...common}>
                    <path d="M12 3H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h7" />
                    <path d="M16 3h5v5" />
                    <path d="M21 3l-9 9" />
                    <path d="M14 14l7 7" />
                    <path d="M14 21h7v-7" />
                </svg>
            );
        case "mediations":
            return (
                <svg {...common}>
                    <path d="M12 21s7-4.5 7-10V5l-7-3-7 3v6c0 5.5 7 10 7 10z" />
                    <path d="M9 12l2 2 4-4" />
                </svg>
            );
        case "patrol":
            return (
                <svg {...common}>
                    <path d="M12 22s8-4 8-10V6l-8-4-8 4v6c0 6 8 10 8 10z" />
                    <path d="M9.5 12.5l2 2 3.5-4" />
                </svg>
            );
        case "settings":
            return (
                <svg {...common}>
                    <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
                    <path d="M19.4 15a1.8 1.8 0 0 0 .4 2l.1.1a2 2 0 0 1-1.4 3.4 2 2 0 0 1-1.4-.6l-.1-.1a1.8 1.8 0 0 0-2-.4 1.8 1.8 0 0 0-1.1 1.7V22a2 2 0 0 1-4 0v-.2a1.8 1.8 0 0 0-1.1-1.7 1.8 1.8 0 0 0-2 .4l-.1.1A2 2 0 1 1 2.1 19l.1-.1a1.8 1.8 0 0 0 .4-2 1.8 1.8 0 0 0-1.7-1.1H1a2 2 0 0 1 0-4h-.1a1.8 1.8 0 0 0 1.7-1.1 1.8 1.8 0 0 0-.4-2l-.1-.1A2 2 0 1 1 4.5 4.6l.1.1a1.8 1.8 0 0 0 2 .4 1.8 1.8 0 0 0 1.1-1.7V3a2 2 0 0 1 4 0v.2a1.8 1.8 0 0 0 1.1 1.7 1.8 1.8 0 0 0 2-.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.8 1.8 0 0 0-.4 2 1.8 1.8 0 0 0 1.7 1.1H23a2 2 0 0 1 0 4h-.2a1.8 1.8 0 0 0-1.7 1.1z" />
                </svg>
            );
        default:
            return null;
    }
};

const navItems = (tenant, permissions = {}) => {
    const items = [
        {
            label: "Overview",
            mobileLabel: "Dashboard",
            href: "/dashboard",
            icon: "dashboard",
        },
    ];

    if (permissions.view_incidents || permissions.create_incidents) {
        items.push({
            label: permissions.manage_incidents
                ? "Incidents"
                : "Report an Incident",
            mobileLabel: permissions.manage_incidents ? "Incidents" : "Report",
            href: "/incidents",
            icon: "incidents",
        });
    }

    if (permissions.review_blotter_requests) {
        items.push({
            label: "Blotter Requests",
            mobileLabel: "Blotter",
            href: "/blotter-requests",
            icon: "requests",
        });
    } else if (permissions.request_blotter_copy) {
        items.push({
            label: "My Requests",
            mobileLabel: "Requests",
            href: "/blotter-requests",
            icon: "requests",
        });
    }

    if (permissions.manage_users) {
        items.push({
            label: "Users",
            mobileLabel: "Users",
            href: "/users",
            icon: "users",
        });
        items.push({
            label: "Roles & Permissions",
            mobileLabel: "Roles",
            href: "/roles-permissions",
            icon: "roles",
        });
    }

    if (permissions.manage_branding) {
        items.push({
            label: "Branding",
            mobileLabel: "Branding",
            href: "/branding",
            icon: "branding",
        });
    }

    if (tenant?.plan?.mediation_scheduling && permissions.manage_mediations) {
        items.push({
            label: "Mediations",
            mobileLabel: "Mediations",
            href: "/mediations",
            icon: "mediations",
        });
    }

    if (permissions.manage_patrol_logs) {
        items.push({
            label: "Patrol",
            mobileLabel: "Patrol",
            href: "/patrol",
            icon: "patrol",
        });
    }

    if (permissions.manage_account_settings) {
        items.push({
            label: "Settings",
            mobileLabel: "Settings",
            href: "/settings",
            icon: "settings",
        });
    }

    return items;
};

const ChevronDownIcon = () => (
    <svg
        className="size-4 text-slate-500"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M8 9l4-4 4 4m0 6l-4 4-4-4"
        />
    </svg>
);

const hexToRgba = (hex, alpha) => {
    const normalized = String(hex || "").replace("#", "");

    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return `rgba(99, 91, 255, ${alpha})`;
    }

    const red = parseInt(normalized.slice(0, 2), 16);
    const green = parseInt(normalized.slice(2, 4), 16);
    const blue = parseInt(normalized.slice(4, 6), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

export default function TenantLayout({ children }) {
    const page = usePage();
    const {
        auth,
        current_tenant,
        tenant_permissions,
        app_name,
        flash,
        logo_url,
    } = page.props;
    const user = auth?.user;
    const mustChangePassword = Boolean(user?.must_change_password);
    const items = navItems(current_tenant, tenant_permissions);
    const tenantLabel =
        current_tenant?.sidebar_label || current_tenant?.name || "Tenant";
    const tenantLogo =
        current_tenant?.logo_url || logo_url || "/images/logo.png";
    const tenantTheme = current_tenant?.theme_css_variables || {};
    const tenantPrimary = current_tenant?.theme_primary_color || "#635bff";
    const tenantSidebar = current_tenant?.theme_sidebar_color || "#121621";
    const tenantPrimarySoft = hexToRgba(tenantPrimary, 0.12);
    const tenantPrimaryBadge = hexToRgba(tenantPrimary, 0.1);
    const storageKey = "ui:tenantSidebarCollapsed";
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

    useEffect(() => {
        if (typeof document === "undefined") {
            return;
        }

        const baseTitle = app_name || "Barangay Blotter Tenancy";
        document.title = current_tenant
            ? `${tenantLabel} - ${baseTitle}`
            : baseTitle;

        const ensureIcon = (selector, rel) => {
            let link = document.querySelector(selector);
            if (!link) {
                link = document.createElement("link");
                link.setAttribute("rel", rel);
                document.head.appendChild(link);
            }
            link.setAttribute("type", "image/png");
            link.setAttribute("href", tenantLogo);
        };

        ensureIcon('link[rel="icon"]', "icon");
        ensureIcon('link[rel="shortcut icon"]', "shortcut icon");
    }, [app_name, current_tenant, tenantLabel, tenantLogo]);

    const isActive = (href) => {
        if (href === "/dashboard") return path === href;
        return path.startsWith(href);
    };

    const sidebarWidthClass = sidebarCollapsed ? "lg:pl-20" : "lg:pl-64";

    return (
        <>
            <div
                className={`flex min-h-screen ${
                    mustChangePassword ? "pointer-events-none select-none" : ""
                }`}
                style={{
                    backgroundColor: "var(--color-tenant-bg, #f8fafc)",
                    ...tenantTheme,
                }}
            >
            {/* TENANT APP SIDEBAR - Purple/Indigo Theme */}
            <aside
                className={`fixed inset-y-0 left-0 z-40 hidden flex-col border-r transition-all duration-200 lg:flex ${
                    sidebarCollapsed ? "w-20" : "w-64"
                }`}
                style={{
                    backgroundColor: tenantSidebar,
                    borderColor: "rgba(255, 255, 255, 0.05)",
                }}
            >
                <div
                    className={`flex h-16 shrink-0 items-center gap-2 border-b ${
                        sidebarCollapsed ? "px-4" : "px-6"
                    }`}
                    style={{ borderColor: "rgba(255, 255, 255, 0.05)" }}
                >
                    <img
                        src={tenantLogo}
                        alt="Logo"
                        className="h-9 w-9 shrink-0 rounded-devias object-contain"
                        style={{
                            backgroundColor: "rgba(99, 91, 255, 0.2)",
                        }}
                        onError={(e) => {
                            e.target.style.display = "none";
                            e.target.nextSibling?.classList.remove("hidden");
                        }}
                    />
                    <span
                        className="hidden size-9 items-center justify-center rounded-devias text-sm font-bold text-white"
                        style={{
                            backgroundColor:
                                "var(--color-tenant-primary, #635bff)",
                        }}
                        aria-hidden
                    >
                        MB
                    </span>
                    {!sidebarCollapsed && (
                        <span className="truncate text-base font-semibold text-white">
                            {tenantLabel}
                        </span>
                    )}
                </div>
                <nav
                    className={`flex-1 space-y-0.5 overflow-y-auto py-4 ${
                        sidebarCollapsed ? "px-2" : "px-3"
                    }`}
                    aria-label="Main"
                >
                    {items.map((item) => (
                        <div key={item.href} className="relative">
                            <span
                                className={`pointer-events-none absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r transition-all duration-200 ${
                                    isActive(item.href)
                                        ? "opacity-100"
                                        : "opacity-0"
                                }`}
                                style={{
                                    backgroundColor: isActive(item.href)
                                        ? "var(--color-tenant-primary, #635bff)"
                                        : "transparent",
                                }}
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
                                        ? hexToRgba(tenantPrimary, 0.22)
                                        : "transparent",
                                }}
                                title={sidebarCollapsed ? item.label : undefined}
                            >
                                <span
                                    className="grid size-8 place-items-center rounded-devias transition-all duration-200"
                                    style={{
                                        backgroundColor: isActive(item.href)
                                            ? hexToRgba(tenantPrimary, 0.2)
                                            : "rgba(255,255,255,0.04)",
                                        color: isActive(item.href)
                                            ? "#fff"
                                            : undefined,
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
                    ))}
                </nav>
            </aside>

            <div className={`flex flex-1 flex-col ${sidebarWidthClass}`}>
                <header className="sticky top-0 z-30 flex h-16 shrink-0 flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm sm:gap-4 sm:px-6">
                    <button
                        type="button"
                        onClick={toggleSidebar}
                        className="hidden rounded-devias border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 lg:inline-flex"
                        title={
                            sidebarCollapsed
                                ? "Expand sidebar"
                                : "Collapse sidebar"
                        }
                    >
                        {sidebarCollapsed ? "»" : "«"}
                    </button>
                    <div className="flex flex-1 flex-wrap items-center gap-1 lg:hidden">
                        {items.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-devias px-2.5 py-1.5 text-sm font-medium transition ${
                                    isActive(item.href)
                                        ? "font-semibold"
                                        : "text-slate-600 hover:bg-slate-100"
                                }`}
                                style={{
                                    color: isActive(item.href)
                                        ? "var(--color-tenant-primary, #635bff)"
                                        : "inherit",
                                    backgroundColor: isActive(item.href)
                                        ? tenantPrimarySoft
                                        : "transparent",
                                }}
                            >
                                {item.mobileLabel ?? item.label}
                            </Link>
                        ))}
                    </div>
                    {/* TENANT BADGE - Green/Emerald for barangay identity */}
                    {current_tenant && (
                        <div
                            className="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                            style={{
                                borderColor:
                                    "var(--color-tenant-primary, #635bff)",
                                backgroundColor: tenantPrimaryBadge,
                                color: "var(--color-tenant-primary, #635bff)",
                            }}
                        >
                            🏘️ {tenantLabel}
                        </div>
                    )}
                    <div className="ml-auto flex items-center justify-end gap-3">
                        {current_tenant && (
                            <Link
                                href="/tenant/select"
                                className="flex items-center gap-2 rounded-devias border border-slate-200 px-3 py-2 text-sm font-medium transition"
                                style={{
                                    backgroundColor: "#f8fafc",
                                    color: "#475569",
                                }}
                                onMouseEnter={(e) => {
                                    e.target.style.backgroundColor = "#f1f5f9";
                                }}
                                onMouseLeave={(e) => {
                                    e.target.style.backgroundColor = "#f8fafc";
                                }}
                            >
                                <span>Switch</span>
                                <ChevronDownIcon />
                            </Link>
                        )}
                        <span className="text-sm text-slate-600">
                            {user?.name}
                        </span>
                        <button
                            type="button"
                            onClick={() => router.post("/logout")}
                            className="rounded-devias border border-slate-200 px-3 py-2 text-sm font-medium transition"
                            style={{
                                backgroundColor: "#ffffff",
                                color: "#475569",
                            }}
                            onMouseEnter={(e) => {
                                e.target.style.backgroundColor = "#f8fafc";
                            }}
                            onMouseLeave={(e) => {
                                e.target.style.backgroundColor = "#ffffff";
                            }}
                        >
                            Logout
                        </button>
                    </div>
                </header>

                <main
                    className="flex-1 p-6"
                    style={{
                        backgroundColor: "var(--color-tenant-bg, #f8fafc)",
                    }}
                >
                    {flash?.success && (
                        <div className="mb-4 rounded-devias border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 rounded-devias border border-red-200 bg-red-50 p-4 text-red-800">
                            {flash.error}
                        </div>
                    )}
                    {flash?.warning && (
                        <div className="mb-4 rounded-devias border border-amber-200 bg-amber-50 p-4 text-amber-800">
                            {flash.warning}
                        </div>
                    )}
                    {children}
                </main>
            </div>
            </div>
            <ForcedPasswordChangeModal open={mustChangePassword} />
        </>
    );
}
