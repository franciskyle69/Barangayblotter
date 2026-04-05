import { router } from "@inertiajs/react";
import { useState } from "react";
import CentralLayout from "../Layouts/CentralLayout";

const PER_PAGE_OPTIONS = [10, 25, 30, 50, 100];

export default function ActivityLogs({
    logs,
    filters,
    actions,
    tenants,
    setupRequired,
    setupMessage,
    setupCommand,
    setupError,
}) {
    const [form, setForm] = useState({
        search: filters?.search ?? "",
        action: filters?.action ?? "",
        tenant_id: filters?.tenant_id ?? "",
        per_page: filters?.per_page ?? "30",
    });

    const submitFilters = (event) => {
        event.preventDefault();

        router.get("/super/activity-logs", form, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        const cleared = {
            search: "",
            action: "",
            tenant_id: "",
            per_page: "30",
        };

        setForm(cleared);
        router.get("/super/activity-logs", cleared, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const items = Array.isArray(logs?.data) ? logs.data : [];

    return (
        <CentralLayout>
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold text-slate-800">
                        Central Activity Logs
                    </h1>
                    <div className="flex items-center gap-3 text-sm text-slate-500">
                        <span>
                            Showing {logs?.from ?? 0}-{logs?.to ?? 0} of{" "}
                            {logs?.total ?? 0}
                        </span>
                        <label className="flex items-center gap-2">
                            <span>Rows</span>
                            <select
                                value={form.per_page}
                                onChange={(e) =>
                                    setForm((prev) => ({
                                        ...prev,
                                        per_page: e.target.value,
                                    }))
                                }
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700"
                            >
                                {PER_PAGE_OPTIONS.map((option) => (
                                    <option key={option} value={String(option)}>
                                        {option}
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>
                </div>

                {setupRequired && (
                    <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900">
                        <p className="text-sm font-semibold">
                            Activity logs setup required
                        </p>
                        <p className="mt-1 text-sm">
                            {setupMessage ||
                                "Activity logs table is not ready yet."}
                        </p>
                        {setupCommand && (
                            <p className="mt-2 rounded bg-amber-100 px-2 py-1 font-mono text-xs">
                                {setupCommand}
                            </p>
                        )}
                        {setupError && (
                            <p className="mt-2 text-xs text-amber-800">
                                {setupError}
                            </p>
                        )}
                    </div>
                )}

                <form
                    onSubmit={submitFilters}
                    className="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-5"
                >
                    <input
                        type="text"
                        value={form.search}
                        onChange={(e) =>
                            setForm((prev) => ({
                                ...prev,
                                search: e.target.value,
                            }))
                        }
                        placeholder="Search description/user/target"
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />

                    <select
                        value={form.action}
                        onChange={(e) =>
                            setForm((prev) => ({
                                ...prev,
                                action: e.target.value,
                            }))
                        }
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All actions</option>
                        {(actions || []).map((action) => (
                            <option key={action} value={action}>
                                {action}
                            </option>
                        ))}
                    </select>

                    <select
                        value={form.tenant_id}
                        onChange={(e) =>
                            setForm((prev) => ({
                                ...prev,
                                tenant_id: e.target.value,
                            }))
                        }
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All barangays</option>
                        {(tenants || []).map((tenant) => (
                            <option key={tenant.id} value={tenant.id}>
                                {tenant.name}
                            </option>
                        ))}
                    </select>

                    <div className="flex gap-2 lg:col-span-2 lg:justify-end">
                        <button
                            type="submit"
                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Apply
                        </button>
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Clear
                        </button>
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th className="px-3 py-2">Time</th>
                                    <th className="px-3 py-2">Actor</th>
                                    <th className="px-3 py-2">Action</th>
                                    <th className="px-3 py-2">Description</th>
                                    <th className="px-3 py-2">Target</th>
                                    <th className="px-3 py-2">Source</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {items.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-3 py-8 text-center text-slate-500"
                                        >
                                            No activity logs found for this
                                            filter.
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((log) => (
                                        <tr key={log.id}>
                                            <td className="px-3 py-2 text-slate-600">
                                                {log.created_at_human || "-"}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="font-medium text-slate-800">
                                                    {log.actor_name || "System"}
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    {log.actor_email || "-"}
                                                </div>
                                            </td>
                                            <td className="px-3 py-2 font-mono text-xs text-indigo-700">
                                                {log.action}
                                            </td>
                                            <td className="px-3 py-2 text-slate-700">
                                                {log.description}
                                            </td>
                                            <td className="px-3 py-2 text-slate-600">
                                                {log.target_type
                                                    ? `${log.target_type}:${log.target_id || "-"}`
                                                    : "-"}
                                            </td>
                                            <td className="px-3 py-2 text-xs text-slate-500">
                                                {log.ip_address || "-"}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                        <button
                            type="button"
                            disabled={!logs?.prev_page_url}
                            onClick={() =>
                                logs?.prev_page_url &&
                                router.visit(logs.prev_page_url, {
                                    preserveScroll: true,
                                })
                            }
                            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Previous
                        </button>
                        <span className="text-sm text-slate-600">
                            Page {logs?.current_page ?? 1} of{" "}
                            {logs?.last_page ?? 1}
                        </span>
                        <button
                            type="button"
                            disabled={!logs?.next_page_url}
                            onClick={() =>
                                logs?.next_page_url &&
                                router.visit(logs.next_page_url, {
                                    preserveScroll: true,
                                })
                            }
                            className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </CentralLayout>
    );
}
