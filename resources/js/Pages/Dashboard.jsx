import { Link } from "@inertiajs/react";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Legend,
} from "recharts";
import TenantLayout from "./Layouts/TenantLayout";

const STATUS_LABELS = {
    open: "Open",
    under_mediation: "Under Mediation",
    settled: "Settled",
    escalated_to_barangay: "Escalated",
};
const CHART_COLORS = ["#f59e0b", "#3b82f6", "#10b981", "#475569"];

const TrendPill = ({ value, tone = "neutral" }) => {
    const isUp = typeof value === "number" && value > 0;
    const isDown = typeof value === "number" && value < 0;
    const label =
        typeof value !== "number" || Number.isNaN(value)
            ? "—"
            : `${isUp ? "+" : ""}${value.toFixed(0)}%`;

    const toneClass =
        tone === "good"
            ? "bg-emerald-50 text-emerald-700 ring-emerald-200"
            : tone === "bad"
              ? "bg-rose-50 text-rose-700 ring-rose-200"
              : "bg-slate-50 text-slate-700 ring-slate-200";

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold ring-1 ${toneClass}`}
            title="Trend pill (add historical tracking to enable real trends)"
        >
            <span aria-hidden className="text-[12px] leading-none">
                {isUp ? "↗" : isDown ? "↘" : "•"}
            </span>
            {label}
        </span>
    );
};

const StatCard = ({
    label,
    value,
    sub,
    icon,
    accentClass = "text-slate-900",
    pill,
}) => {
    return (
        <div className="group relative overflow-hidden rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-slate-500">
                        {label}
                    </p>
                    <p className={`mt-2 text-2xl font-bold ${accentClass}`}>
                        {value}
                    </p>
                    {sub && (
                        <p className="mt-1 text-xs text-slate-400">{sub}</p>
                    )}
                </div>
                <div className="flex flex-col items-end gap-2">
                    {pill}
                    <span className="grid size-10 place-items-center rounded-devias bg-slate-900/5 text-slate-700 transition group-hover:bg-slate-900/10">
                        {icon}
                    </span>
                </div>
            </div>
            <div className="pointer-events-none absolute -right-10 -top-10 size-24 rounded-full bg-slate-900/5 blur-2xl" />
        </div>
    );
};

export default function Dashboard({
    tenant,
    role,
    stats,
    recentIncidents,
    myBlotterRequests,
    canSeeAnalytics,
}) {
    const totalStatus =
        stats.open + stats.under_mediation + stats.settled + stats.escalated;
    const pieData = [
        { name: STATUS_LABELS.open, value: stats.open, fill: CHART_COLORS[0] },
        {
            name: STATUS_LABELS.under_mediation,
            value: stats.under_mediation,
            fill: CHART_COLORS[1],
        },
        {
            name: STATUS_LABELS.settled,
            value: stats.settled,
            fill: CHART_COLORS[2],
        },
        {
            name: STATUS_LABELS.escalated_to_barangay,
            value: stats.escalated,
            fill: CHART_COLORS[3],
        },
    ].filter((d) => d.value > 0);

    const roleLabels = {
        barangay_admin: "Barangay Admin",
        purok_secretary: "Barangay Secretary",
        purok_leader: "Barangay Captain",
        community_watch: "Community Watch",
        mediator: "Community Mediator",
        resident: "Resident",
        citizen: "Citizen",
    };

    const StatusBadge = ({ status }) => {
        const tone =
            status === "open"
                ? "bg-amber-50 text-amber-800 ring-amber-200"
                : status === "under_mediation"
                  ? "bg-blue-50 text-blue-800 ring-blue-200"
                  : status === "settled"
                    ? "bg-emerald-50 text-emerald-800 ring-emerald-200"
                    : "bg-slate-50 text-slate-800 ring-slate-200";
        const label =
            STATUS_LABELS[status] ??
            String(status || "")
                .replace(/_/g, " ")
                .replace(/\b\w/g, (m) => m.toUpperCase());

        return (
            <span
                className={`inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold ring-1 ${tone}`}
            >
                {label}
            </span>
        );
    };

    const nearingLimit =
        tenant?.plan &&
        !tenant.plan.has_unlimited &&
        typeof tenant.plan.incident_limit_per_month === "number" &&
        tenant.plan.incident_limit_per_month > 0 &&
        stats.incidents_this_month / tenant.plan.incident_limit_per_month >= 0.8;

    const alerts = [
        nearingLimit
            ? {
                  title: "Incident limit almost reached",
                  body: `You’ve used ${stats.incidents_this_month}/${tenant.plan.incident_limit_per_month} incidents for this month.`,
                  ctaLabel: "View incidents",
                  ctaHref: "/incidents",
              }
            : null,
        stats.open > 0
            ? {
                  title: "Open incidents need attention",
                  body: `${stats.open} incident(s) are still open.`,
                  ctaLabel: "Review open",
                  ctaHref: "/incidents",
              }
            : null,
    ].filter(Boolean);

    return (
        <TenantLayout>
            {tenant && (
                <>
                    <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">
                                Overview
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                {roleLabels[role] || role} · {tenant.name}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Link
                                href="/incidents/create"
                                className="inline-flex items-center gap-2 rounded-devias bg-devias-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-95"
                            >
                                <span aria-hidden>＋</span> New incident
                            </Link>
                            <Link
                                href="/support/create"
                                className="inline-flex items-center gap-2 rounded-devias border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                <span aria-hidden>?</span> Get support
                            </Link>
                        </div>
                    </div>

                    <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <StatCard
                            label="Incidents this month"
                            value={stats.incidents_this_month}
                            sub={
                                tenant.plan?.has_unlimited
                                    ? "Unlimited plan"
                                    : `Limit: ${tenant.plan?.incident_limit_per_month}/month`
                            }
                            pill={<TrendPill value={null} />}
                            icon={
                                <svg
                                    viewBox="0 0 24 24"
                                    className="size-5"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                >
                                    <path d="M4 19V5" />
                                    <path d="M4 19h16" />
                                    <path d="M7 15l4-4 3 3 6-7" />
                                </svg>
                            }
                        />
                        <StatCard
                            label="Open"
                            value={stats.open}
                            accentClass="text-amber-600"
                            pill={
                                <TrendPill
                                    value={null}
                                    tone={stats.open > 0 ? "bad" : "good"}
                                />
                            }
                            icon={
                                <svg
                                    viewBox="0 0 24 24"
                                    className="size-5"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                >
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <path d="M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0z" />
                                </svg>
                            }
                        />
                        <StatCard
                            label="Under mediation"
                            value={stats.under_mediation}
                            accentClass="text-blue-600"
                            pill={<TrendPill value={null} />}
                            icon={
                                <svg
                                    viewBox="0 0 24 24"
                                    className="size-5"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                >
                                    <path d="M7 11h10" />
                                    <path d="M7 15h6" />
                                    <path d="M6 3h12a2 2 0 0 1 2 2v14l-4-3H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                </svg>
                            }
                        />
                        <StatCard
                            label="Settled"
                            value={stats.settled}
                            accentClass="text-emerald-600"
                            pill={<TrendPill value={null} tone="good" />}
                            icon={
                                <svg
                                    viewBox="0 0 24 24"
                                    className="size-5"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                >
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
                            }
                        />
                        <StatCard
                            label="Escalated"
                            value={stats.escalated}
                            accentClass="text-slate-700"
                            pill={<TrendPill value={null} />}
                            icon={
                                <svg
                                    viewBox="0 0 24 24"
                                    className="size-5"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                >
                                    <path d="M12 2v20" />
                                    <path d="M19 9l-7-7-7 7" />
                                </svg>
                            }
                        />
                    </div>

                    <div className="mb-8 grid gap-6 lg:grid-cols-3">
                        <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm lg:col-span-2">
                            <div className="mb-4 flex items-center justify-between gap-3">
                                <h2 className="font-semibold text-slate-900">
                                    Alerts & insights
                                </h2>
                                <span className="text-xs font-medium text-slate-500">
                                    Today
                                </span>
                            </div>
                            {alerts.length === 0 ? (
                                <div className="rounded-devias border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                                    No alerts right now. You’re all set.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {alerts.map((a) => (
                                        <div
                                            key={a.title}
                                            className="flex flex-wrap items-start justify-between gap-3 rounded-devias border border-slate-200/70 bg-white p-4 hover:bg-slate-50"
                                        >
                                            <div>
                                                <p className="text-sm font-semibold text-slate-900">
                                                    {a.title}
                                                </p>
                                                <p className="mt-1 text-sm text-slate-600">
                                                    {a.body}
                                                </p>
                                            </div>
                                            <Link
                                                href={a.ctaHref}
                                                className="inline-flex items-center rounded-devias border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                            >
                                                {a.ctaLabel}
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                        <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
                            <h2 className="mb-4 font-semibold text-slate-900">
                                Quick actions
                            </h2>
                            <div className="grid gap-2">
                                <Link
                                    href="/incidents/create"
                                    className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
                                >
                                    Create incident
                                    <p className="mt-1 text-xs font-medium text-slate-500">
                                        Report a new incident record
                                    </p>
                                </Link>
                                <Link
                                    href="/blotter-requests"
                                    className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
                                >
                                    View requests
                                    <p className="mt-1 text-xs font-medium text-slate-500">
                                        Track blotter copy requests
                                    </p>
                                </Link>
                                <Link
                                    href="/support/create"
                                    className="rounded-devias border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
                                >
                                    Contact support
                                    <p className="mt-1 text-xs font-medium text-slate-500">
                                        Open a support ticket
                                    </p>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Charts: show when plan has analytics (even with zero data) */}
                    {canSeeAnalytics && (
                        <div className="mb-8 grid gap-6 lg:grid-cols-2">
                            {/* GitHub-style donut: incidents by status */}
                            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
                                <h2 className="mb-4 font-semibold text-slate-900">
                                    Incidents by status
                                </h2>
                                <div className="flex flex-col items-center sm:flex-row sm:items-start">
                                    <div className="h-52 w-52 shrink-0">
                                        <ResponsiveContainer
                                            width="100%"
                                            height="100%"
                                        >
                                            {pieData.length > 0 ? (
                                                <PieChart>
                                                    <Pie
                                                        data={pieData}
                                                        cx="50%"
                                                        cy="50%"
                                                        innerRadius={52}
                                                        outerRadius={88}
                                                        paddingAngle={1}
                                                        dataKey="value"
                                                        stroke="none"
                                                    >
                                                        {pieData.map(
                                                            (entry, i) => (
                                                                <Cell
                                                                    key={i}
                                                                    fill={
                                                                        entry.fill
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </Pie>
                                                    <Tooltip
                                                        contentStyle={{
                                                            borderRadius: "8px",
                                                            border: "1px solid #e2e8f0",
                                                            boxShadow:
                                                                "0 1px 3px rgba(0,0,0,0.08)",
                                                        }}
                                                        formatter={(
                                                            value,
                                                            name,
                                                        ) => [
                                                            `${value} (${totalStatus ? Math.round((Number(value) / totalStatus) * 100) : 0}%)`,
                                                            name,
                                                        ]}
                                                    />
                                                </PieChart>
                                            ) : (
                                                <div className="flex h-full w-full items-center justify-center rounded-full bg-slate-50 text-sm text-slate-500">
                                                    No incident data yet
                                                </div>
                                            )}
                                        </ResponsiveContainer>
                                    </div>
                                    {pieData.length > 0 && (
                                        <Legend
                                            layout="vertical"
                                            align="right"
                                            verticalAlign="middle"
                                            wrapperStyle={{ paddingLeft: 16 }}
                                            formatter={(value, entry) => (
                                                <span className="text-sm text-slate-700">
                                                    <span
                                                        className="mr-2 inline-block h-3 w-3 shrink-0 rounded-sm"
                                                        style={{
                                                            backgroundColor:
                                                                entry.color,
                                                        }}
                                                    />
                                                    {value}{" "}
                                                    <span className="text-slate-500">
                                                        (
                                                        {totalStatus
                                                            ? Math.round(
                                                                  (entry.payload
                                                                      .value /
                                                                      totalStatus) *
                                                                      100,
                                                              )
                                                            : 0}
                                                        %)
                                                    </span>
                                                </span>
                                            )}
                                        />
                                    )}
                                </div>
                            </div>
                            {/* Bar chart (same data; always show so chart area is visible) */}
                            <div className="rounded-devias border border-slate-200/80 bg-white p-5 shadow-sm">
                                <h2 className="mb-4 font-semibold text-slate-900">
                                    Incidents by status (bar)
                                </h2>
                                <div className="h-52">
                                    <ResponsiveContainer
                                        width="100%"
                                        height="100%"
                                    >
                                        <BarChart
                                            data={[
                                                {
                                                    name: STATUS_LABELS.open,
                                                    count: stats.open,
                                                    fill: CHART_COLORS[0],
                                                },
                                                {
                                                    name: STATUS_LABELS.under_mediation,
                                                    count: stats.under_mediation,
                                                    fill: CHART_COLORS[1],
                                                },
                                                {
                                                    name: STATUS_LABELS.settled,
                                                    count: stats.settled,
                                                    fill: CHART_COLORS[2],
                                                },
                                                {
                                                    name: STATUS_LABELS.escalated_to_barangay,
                                                    count: stats.escalated,
                                                    fill: CHART_COLORS[3],
                                                },
                                            ]}
                                            margin={{
                                                top: 8,
                                                right: 8,
                                                left: 0,
                                                bottom: 0,
                                            }}
                                        >
                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                                stroke="#e2e8f0"
                                            />
                                            <XAxis
                                                dataKey="name"
                                                tick={{ fontSize: 12 }}
                                                stroke="#64748b"
                                            />
                                            <YAxis
                                                tick={{ fontSize: 12 }}
                                                stroke="#64748b"
                                                allowDecimals={false}
                                            />
                                            <Tooltip
                                                contentStyle={{
                                                    borderRadius: "8px",
                                                    border: "1px solid #e2e8f0",
                                                }}
                                                formatter={(value) => [
                                                    value,
                                                    "Count",
                                                ]}
                                            />
                                            <Bar
                                                dataKey="count"
                                                radius={[4, 4, 0, 0]}
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-2">
                        <div className="rounded-devias border border-slate-200/80 bg-white shadow-sm">
                            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <h2 className="font-semibold text-slate-900">
                                    Recent incidents
                                </h2>
                                <Link
                                    href="/incidents"
                                    className="text-sm font-medium text-devias-primary hover:underline"
                                >
                                    View all
                                </Link>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {recentIncidents?.length ? (
                                    recentIncidents.map((inc) => (
                                        <Link
                                            key={inc.id}
                                            href={`/incidents/${inc.id}`}
                                            className="flex items-center justify-between px-5 py-3 transition hover:bg-slate-50"
                                        >
                                            <span className="flex items-center gap-2 font-medium text-slate-800">
                                                {inc.blotter_number || "N/A"}
                                                <StatusBadge status={inc.status} />
                                            </span>
                                            <span className="text-sm text-slate-500">
                                                {inc.incident_type} ·{" "}
                                                {inc.incident_date}
                                            </span>
                                        </Link>
                                    ))
                                ) : (
                                    <p className="px-5 py-6 text-sm text-slate-500">
                                        No incidents yet.
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="rounded-devias border border-slate-200/80 bg-white shadow-sm">
                            <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                                <h2 className="font-semibold text-slate-900">
                                    My blotter requests
                                </h2>
                                <Link
                                    href="/blotter-requests"
                                    className="text-sm font-medium text-devias-primary hover:underline"
                                >
                                    View all
                                </Link>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {myBlotterRequests?.length ? (
                                    myBlotterRequests.map((req) => (
                                        <div
                                            key={req.id}
                                            className="flex items-center justify-between px-5 py-3"
                                        >
                                            <span className="text-sm text-slate-800">
                                                Incident{" "}
                                                {req.incident?.blotter_number ||
                                                    req.incident_id}
                                            </span>
                                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                                {req.status}
                                            </span>
                                        </div>
                                    ))
                                ) : (
                                    <p className="px-5 py-6 text-sm text-slate-500">
                                        No blotter requests.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </>
            )}
        </TenantLayout>
    );
}
