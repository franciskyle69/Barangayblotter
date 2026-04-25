import { Link, router } from "@inertiajs/react";
import TenantLayout from "../Layouts/TenantLayout";

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

const labelize = (value) =>
    typeof value === "string"
        ? value
              .split("_")
              .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
              .join(" ")
        : value;

export default function SupportIndex({ tickets }) {
    const items = tickets?.data ?? [];
    const links = tickets?.links ?? [];

    const goto = (url) => {
        if (!url) return;
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <TenantLayout>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">
                        Support
                    </h1>
                    <p className="text-sm text-slate-500">
                        File a complaint, report a bug, or request help from
                        the central team.
                    </p>
                </div>
                <Link
                    href="/support/create"
                    className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700"
                >
                    New Ticket
                </Link>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow">
                <table className="min-w-full divide-y divide-slate-200">
                    <thead className="bg-slate-50">
                        <tr className="text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                            <th className="px-4 py-2">#</th>
                            <th className="px-4 py-2">Subject</th>
                            <th className="px-4 py-2">Category</th>
                            <th className="px-4 py-2">Priority</th>
                            <th className="px-4 py-2">Status</th>
                            <th className="px-4 py-2">Last activity</th>
                            <th className="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 bg-white text-sm">
                        {items.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-10 text-center text-slate-500"
                                >
                                    No tickets yet. Click{" "}
                                    <span className="font-medium">
                                        New Ticket
                                    </span>{" "}
                                    to send your first message to central.
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
                                            href={`/support/${t.id}`}
                                            className="text-sm font-medium text-emerald-700 hover:underline"
                                        >
                                            View
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
                                    ? "border-emerald-600 bg-emerald-600 text-white"
                                    : "border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                            } ${!l.url ? "opacity-40" : ""}`}
                            dangerouslySetInnerHTML={{ __html: l.label }}
                        />
                    ))}
                </div>
            )}
        </TenantLayout>
    );
}
