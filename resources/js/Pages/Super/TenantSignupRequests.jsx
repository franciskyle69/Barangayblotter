import { Link, router } from "@inertiajs/react";
import { useMemo, useState } from "react";
import CentralLayout from "../Layouts/CentralLayout";

/**
 * Super-admin review screen for tenant (barangay) signup requests.
 *
 * Previous bug: the per-row plan override dropdown wrote to a shared
 * `approveForm.plan_id` which was then immediately overwritten in
 * `approve()` with the request's originally-requested plan. That meant
 * super admins could change the plan in the UI but the backend was
 * always provisioned with the request's default. We now keep per-row
 * draft state (plan_id, review_notes) in a single `drafts` map so the
 * submitted value is exactly what the admin saw.
 */
export default function TenantSignupRequests({ requests = [], plans = [] }) {
    const fallbackPlanId = plans[0]?.id ?? "";

    // drafts[requestId] = { plan_id, review_notes }
    const initialDrafts = useMemo(() => {
        const map = {};
        for (const req of requests) {
            if (req.status !== "pending") continue;
            map[req.id] = {
                plan_id: req.requested_plan_id || fallbackPlanId || "",
                review_notes: "",
            };
        }
        return map;
    }, [requests, fallbackPlanId]);

    const [drafts, setDrafts] = useState(initialDrafts);
    const [processingId, setProcessingId] = useState(null);

    const pending = requests.filter((r) => r.status === "pending");
    const processed = requests.filter((r) => r.status !== "pending");

    const updateDraft = (id, patch) =>
        setDrafts((prev) => ({
            ...prev,
            [id]: { ...(prev[id] ?? {}), ...patch },
        }));

    const approve = (id) => {
        if (processingId) return;
        const draft = drafts[id] ?? {};
        setProcessingId(id);
        router.post(
            `/super/tenant-signup-requests/${id}/approve`,
            {
                plan_id: draft.plan_id || fallbackPlanId,
                review_notes: draft.review_notes ?? "",
            },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    const reject = (id) => {
        if (processingId) return;
        const draft = drafts[id] ?? {};
        setProcessingId(id);
        router.post(
            `/super/tenant-signup-requests/${id}/reject`,
            { review_notes: draft.review_notes ?? "" },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    return (
        <CentralLayout>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-slate-800">
                    Tenant Signup Requests
                </h1>
            </div>

            <section className="rounded-lg bg-white p-5 shadow-sm">
                <h2 className="mb-3 text-lg font-semibold text-slate-800">
                    Pending Requests ({pending.length})
                </h2>
                {pending.length === 0 ? (
                    <p className="text-sm text-slate-500">
                        No pending requests.
                    </p>
                ) : (
                    <div className="space-y-4">
                        {pending.map((req) => {
                            const draft = drafts[req.id] ?? {};
                            const isBusy = processingId === req.id;

                            return (
                                <article
                                    key={req.id}
                                    className="rounded-lg border border-slate-200 p-4"
                                >
                                    <dl className="grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                                        <Field label="Tenant" value={req.tenant_name} />
                                        <Field label="Slug" value={req.slug} />
                                        <Field label="Subdomain" value={req.subdomain || "-"} />
                                        <Field
                                            label="Custom Domain"
                                            value={req.custom_domain || "-"}
                                        />
                                        <Field
                                            label="Requested Plan"
                                            value={
                                                req.requested_plan?.name ||
                                                "No preference"
                                            }
                                        />
                                        <Field
                                            label="Requested Admin"
                                            value={`${req.requested_admin_name} (${req.requested_admin_email})`}
                                        />
                                    </dl>

                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        <label className="text-sm">
                                            <span className="mb-1 block font-medium text-slate-600">
                                                Plan to provision
                                            </span>
                                            <select
                                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                value={draft.plan_id ?? ""}
                                                onChange={(e) =>
                                                    updateDraft(req.id, {
                                                        plan_id: e.target.value,
                                                    })
                                                }
                                                disabled={isBusy}
                                            >
                                                {plans.map((p) => (
                                                    <option key={p.id} value={p.id}>
                                                        {p.name} — ₱{p.price_monthly}/mo
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                        <label className="text-sm">
                                            <span className="mb-1 block font-medium text-slate-600">
                                                Review notes (optional)
                                            </span>
                                            <input
                                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                value={draft.review_notes ?? ""}
                                                onChange={(e) =>
                                                    updateDraft(req.id, {
                                                        review_notes: e.target.value,
                                                    })
                                                }
                                                disabled={isBusy}
                                            />
                                        </label>
                                    </div>

                                    <div className="mt-4 flex flex-wrap gap-2">
                                        <Link
                                            href={`/super/tenants/create?signup_request_id=${req.id}`}
                                            className="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                        >
                                            Copy to Add Barangay
                                        </Link>
                                        <button
                                            type="button"
                                            className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            onClick={() => approve(req.id)}
                                            disabled={isBusy}
                                        >
                                            {isBusy ? "Approving…" : "Approve & Provision"}
                                        </button>
                                        <button
                                            type="button"
                                            className="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                            onClick={() => reject(req.id)}
                                            disabled={isBusy}
                                        >
                                            {isBusy ? "Rejecting…" : "Reject"}
                                        </button>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </section>

            <section className="mt-6 rounded-lg bg-white p-5 shadow-sm">
                <h2 className="mb-3 text-lg font-semibold text-slate-800">
                    Processed Requests ({processed.length})
                </h2>
                {processed.length === 0 ? (
                    <p className="text-sm text-slate-500">
                        No processed requests yet.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                        Tenant
                                    </th>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                        Reviewed At
                                    </th>
                                    <th className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                                        Note
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {processed.map((req) => (
                                    <tr key={req.id}>
                                        <td className="px-3 py-2 text-sm">
                                            {req.tenant_name}
                                        </td>
                                        <td className="px-3 py-2 text-sm capitalize">
                                            {req.status}
                                        </td>
                                        <td className="px-3 py-2 text-sm">
                                            {req.reviewed_at
                                                ? new Date(
                                                      req.reviewed_at,
                                                  ).toLocaleString()
                                                : "-"}
                                        </td>
                                        <td className="px-3 py-2 text-sm">
                                            {req.review_notes || "-"}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </CentralLayout>
    );
}

function Field({ label, value }) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </dt>
            <dd className="mt-0.5 break-words text-sm text-slate-800">
                {value}
            </dd>
        </div>
    );
}
