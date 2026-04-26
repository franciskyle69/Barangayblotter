import { Link, router } from "@inertiajs/react";
import { useState } from "react";
import CentralLayout from "../Layouts/CentralLayout";

const STATUS_CLASS = {
    open: "bg-blue-100 text-blue-800",
    in_progress: "bg-amber-100 text-amber-800",
    awaiting_tenant: "bg-purple-100 text-purple-800",
    resolved: "bg-emerald-100 text-emerald-800",
    closed: "bg-slate-200 text-slate-700",
};

const PRIORITY_CLASS = {
    low: "bg-slate-100 text-slate-700",
    normal: "bg-slate-100 text-slate-700",
    high: "bg-orange-100 text-orange-800",
    urgent: "bg-red-100 text-red-800",
};

const labelize = (v) =>
    v
        ? v
              .split("_")
              .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
              .join(" ")
        : v;

export default function SuperSupport({
    tickets,
    statuses,
    filters = {},
    counts = {},
    tenants = [],
}) {
    const items = tickets?.data ?? [];
    const links = tickets?.links ?? [];
    const [status, setStatus] = useState(filters.status || "");
    const [tenantId, setTenantId] = useState(filters.tenant_id || "");

    const apply = (e) => {
        e?.preventDefault?.();
        const params = {};
        if (status) params.status = status;
        if (tenantId) params.tenant_id = tenantId;
        router.get("/super/support", params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const goto = (url) => {
        if (!url) return;
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <CentralLayout>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">
                        Support Queue
                    </h1>
                    <p className="text-sm text-slate-500">
                        Tickets raised by barangays across the platform.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2 text-xs">
                    {statuses.map((s) => (
                        <span
                            key={s}
                            className={`rounded-full px-2 py-1 font-medium ${
                                STATUS_CLASS[s] || "bg-slate-100 text-slate-700"
                            }`}
                        >
                            {labelize(s)}: {counts[s] || 0}
                        </span>
                    ))}
                </div>
            </div>

            <form
                onSubmit={apply}
                className="mb-4 flex flex-wrap items-end gap-2 rounded-lg bg-white p-3 shadow-sm"
            >
                <div>
                    <label className="block text-xs font-medium text-slate-500">
                        Status
                    </label>
                    <select
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                        className="mt-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                    >
                        <option value="">All</option>
                        {statuses.map((s) => (
                            <option key={s} value={s}>
                                {labelize(s)}
                            </option>
                        ))}
                    </select>
                </div>
                <div>
                    <label className="block text-xs font-medium text-slate-500">
                        Barangay
                    </label>
                    <select
                        value={tenantId}
                        onChange={(e) => setTenantId(e.target.value)}
                        className="mt-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                    >
                        <option value="">All</option>
                        {tenants.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                </div>
                <button
                    type="submit"
                    className="rounded-lg bg-slate-700 px-3 py-1.5 text-sm text-white hover:bg-slate-800"
                >
                    Filter
                </button>
                {(status || tenantId) && (
                    <button
                        type="button"
                        onClick={() => {
                            setStatus("");
                            setTenantId("");
                            router.get(
                                "/super/support",
                                {},
                                { preserveState: true, preserveScroll: true }
                            );
                        }}
                        className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
                    >
                        Clear
                    </button>
                )}
            </form>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr className="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <th className="px-4 py-2">#</th>
                            <th className="px-4 py-2">Subject</th>
                            <th className="px-4 py-2">Barangay</th>
                            <th className="px-4 py-2">Category</th>
                            <th className="px-4 py-2">Priority</th>
                            <th className="px-4 py-2">Status</th>
                            <th className="px-4 py-2">Last activity</th>
                            <th className="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 text-sm">
                        {items.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={8}
                                    className="px-4 py-10 text-center text-slate-500"
                                >
                                    No tickets match the current filters.
                                </td>
                            </tr>
                        ) : (
                            items.map((t) => (
                                <tr key={t.id} className="hover:bg-slate-50">
                                    <td className="px-4 py-3 text-slate-500">
                                        #{t.id}
                                    </td>
                                    <td className="px-4 py-3 font-medium text-slate-800">
                                        {t.subject}
                                        <div className="text-xs text-slate-400">
                                            by {t.opened_by_name || "—"}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">
                                        <div className="font-medium text-slate-700">
                                            {t.tenant?.name || "—"}
                                        </div>
                                        {t.tenant?.slug && (
                                            <div className="mt-0.5 font-mono text-xs text-slate-400">
                                                {t.tenant.slug}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-slate-600">
                                        {labelize(t.category)}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                PRIORITY_CLASS[t.priority] ||
                                                "bg-slate-100 text-slate-700"
                                            }`}
                                        >
                                            {labelize(t.priority)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
                                                STATUS_CLASS[t.status] ||
                                                "bg-slate-100 text-slate-700"
                                            }`}
                                        >
                                            {labelize(t.status)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-slate-500">
                                        {t.last_activity_at || t.created_at}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Link
                                            href={`/super/support/${t.id}`}
                                            className="text-sm font-medium text-cyan-700 hover:underline"
                                        >
                                            Open
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {links.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {links.map((l, idx) => (
                        <button
                            key={idx}
                            type="button"
                            disabled={!l.url}
                            onClick={() => goto(l.url)}
                            className={`rounded border px-3 py-1 text-sm ${
                                l.active
                                    ? "border-cyan-600 bg-cyan-600 text-white"
                                    : "border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                            } ${!l.url ? "opacity-40" : ""}`}
                            dangerouslySetInnerHTML={{ __html: l.label }}
                        />
                    ))}
                </div>
            )}
        </CentralLayout>
    );
}
