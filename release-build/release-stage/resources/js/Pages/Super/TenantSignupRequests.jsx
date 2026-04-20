import { Link, useForm, router } from "@inertiajs/react";
import CentralLayout from "../Layouts/CentralLayout";

export default function TenantSignupRequests({ requests = [], plans = [] }) {
    const approveForm = useForm({ plan_id: "", review_notes: "" });
    const rejectForm = useForm({ review_notes: "" });

    const pending = requests.filter((r) => r.status === "pending");
    const processed = requests.filter((r) => r.status !== "pending");

    const approve = (id, requestedPlanId) => {
        approveForm.setData("plan_id", requestedPlanId || plans[0]?.id || "");
        router.post(`/super/tenant-signup-requests/${id}/approve`, {
            plan_id:
                approveForm.data.plan_id || requestedPlanId || plans[0]?.id,
            review_notes: approveForm.data.review_notes,
        });
    };

    const reject = (id) => {
        router.post(`/super/tenant-signup-requests/${id}/reject`, {
            review_notes: rejectForm.data.review_notes,
        });
    };

    return (
        <CentralLayout>
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-2xl font-bold text-slate-800">
                    Tenant Signup Requests
                </h1>
            </div>

            <div className="rounded-lg bg-white p-5 shadow-sm">
                <h2 className="mb-3 text-lg font-semibold text-slate-800">
                    Pending Requests ({pending.length})
                </h2>
                {pending.length === 0 ? (
                    <p className="text-sm text-slate-500">
                        No pending requests.
                    </p>
                ) : (
                    <div className="space-y-4">
                        {pending.map((req) => (
                            <div
                                key={req.id}
                                className="rounded-lg border border-slate-200 p-4"
                            >
                                <div className="grid gap-2 sm:grid-cols-2">
                                    <p>
                                        <span className="font-semibold">
                                            Tenant:
                                        </span>{" "}
                                        {req.tenant_name}
                                    </p>
                                    <p>
                                        <span className="font-semibold">
                                            Slug:
                                        </span>{" "}
                                        {req.slug}
                                    </p>
                                    <p>
                                        <span className="font-semibold">
                                            Subdomain:
                                        </span>{" "}
                                        {req.subdomain || "-"}
                                    </p>
                                    <p>
                                        <span className="font-semibold">
                                            Custom Domain:
                                        </span>{" "}
                                        {req.custom_domain || "-"}
                                    </p>
                                    <p>
                                        <span className="font-semibold">
                                            Requested Plan:
                                        </span>{" "}
                                        {req.requested_plan?.name ||
                                            "No preference"}
                                    </p>
                                    <p>
                                        <span className="font-semibold">
                                            Requested Admin:
                                        </span>{" "}
                                        {req.requested_admin_name} (
                                        {req.requested_admin_email})
                                    </p>
                                    <p>
                                        <span className="font-semibold">
                                            Admin Role:
                                        </span>{" "}
                                        Barangay Admin
                                    </p>
                                </div>

                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    <select
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2"
                                        defaultValue={
                                            req.requested_plan_id ||
                                            plans[0]?.id ||
                                            ""
                                        }
                                        onChange={(e) =>
                                            approveForm.setData(
                                                "plan_id",
                                                e.target.value,
                                            )
                                        }
                                    >
                                        {plans.map((p) => (
                                            <option key={p.id} value={p.id}>
                                                {p.name} - P{p.price_monthly}/mo
                                            </option>
                                        ))}
                                    </select>
                                    <input
                                        className="w-full rounded-lg border border-slate-300 px-3 py-2"
                                        placeholder="Review notes (optional)"
                                        onChange={(e) => {
                                            approveForm.setData(
                                                "review_notes",
                                                e.target.value,
                                            );
                                            rejectForm.setData(
                                                "review_notes",
                                                e.target.value,
                                            );
                                        }}
                                    />
                                </div>

                                <div className="mt-3 flex gap-2">
                                    <Link
                                        href={`/super/tenants/create?signup_request_id=${req.id}`}
                                        className="rounded-lg bg-blue-600 px-3 py-2 text-white hover:bg-blue-700"
                                    >
                                        Copy to Add Barangay
                                    </Link>
                                    <button
                                        type="button"
                                        className="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700"
                                        onClick={() =>
                                            approve(
                                                req.id,
                                                req.requested_plan_id,
                                            )
                                        }
                                    >
                                        Approve and Provision
                                    </button>
                                    <button
                                        type="button"
                                        className="rounded-lg bg-red-600 px-3 py-2 text-white hover:bg-red-700"
                                        onClick={() => reject(req.id)}
                                    >
                                        Reject
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-6 rounded-lg bg-white p-5 shadow-sm">
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
                                    <th className="px-3 py-2 text-left text-xs font-medium text-slate-500">
                                        Tenant
                                    </th>
                                    <th className="px-3 py-2 text-left text-xs font-medium text-slate-500">
                                        Status
                                    </th>
                                    <th className="px-3 py-2 text-left text-xs font-medium text-slate-500">
                                        Reviewed At
                                    </th>
                                    <th className="px-3 py-2 text-left text-xs font-medium text-slate-500">
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
            </div>
        </CentralLayout>
    );
}
