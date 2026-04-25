import { Link, useForm } from "@inertiajs/react";
import CentralLayout from "../Layouts/CentralLayout";
import FormErrorSummary, {
    FieldError,
} from "../../Components/FormErrorSummary";

const STATUS_CLASS = {
    open: "bg-blue-100 text-blue-800",
    in_progress: "bg-amber-100 text-amber-800",
    awaiting_tenant: "bg-purple-100 text-purple-800",
    resolved: "bg-emerald-100 text-emerald-800",
    closed: "bg-slate-200 text-slate-700",
};

const labelize = (v) =>
    v
        ? v
              .split("_")
              .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
              .join(" ")
        : v;

export default function SuperSupportShow({ ticket, statuses }) {
    const replyForm = useForm({ body: "", next_status: "" });
    const statusForm = useForm({ status: ticket.status, closure_note: "" });

    const submitReply = (e) => {
        e.preventDefault();
        replyForm.post(`/super/support/${ticket.id}/reply`, {
            preserveScroll: true,
            onSuccess: () => replyForm.reset("body", "next_status"),
        });
    };

    const submitStatus = (e) => {
        e.preventDefault();
        statusForm.post(`/super/support/${ticket.id}/status`, {
            preserveScroll: true,
        });
    };

    return (
        <CentralLayout>
            <div className="mb-4">
                <div className="text-sm text-slate-500">
                    <Link
                        href="/super/support"
                        className="hover:text-slate-700"
                    >
                        Support Queue
                    </Link>
                    <span className="mx-2">/</span>
                    <span>#{ticket.id}</span>
                </div>
                <h1 className="mt-1 text-2xl font-bold text-slate-800">
                    {ticket.subject}
                </h1>
                <div className="mt-2 flex flex-wrap gap-2 text-xs">
                    <span
                        className={`rounded-full px-2 py-0.5 font-medium ${
                            STATUS_CLASS[ticket.status] ||
                            "bg-slate-100 text-slate-700"
                        }`}
                    >
                        {labelize(ticket.status)}
                    </span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                        {labelize(ticket.category)}
                    </span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                        Priority: {labelize(ticket.priority)}
                    </span>
                    <span className="rounded-full bg-cyan-100 px-2 py-0.5 font-medium text-cyan-800">
                        {ticket.tenant?.name || "—"}
                    </span>
                </div>
                <div className="mt-2 text-xs text-slate-500">
                    Opened by {ticket.opened_by_name || "—"}
                    {ticket.opened_by_email
                        ? ` <${ticket.opened_by_email}>`
                        : ""}{" "}
                    on {ticket.created_at}
                </div>
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div className="space-y-3 lg:col-span-2">
                    {ticket.messages.map((m) => {
                        const isSuper = m.author_scope === "super_admin";
                        return (
                            <div
                                key={m.id}
                                className={`flex ${
                                    isSuper ? "justify-end" : "justify-start"
                                }`}
                            >
                                <div
                                    className={`max-w-[85%] rounded-lg p-4 shadow-sm ${
                                        isSuper
                                            ? "bg-cyan-50 text-slate-800"
                                            : "bg-white text-slate-800"
                                    }`}
                                >
                                    <div className="flex items-center justify-between gap-2 text-xs text-slate-500">
                                        <span className="font-medium">
                                            {isSuper
                                                ? `${m.author_name} (Central)`
                                                : `${
                                                      m.author_name ||
                                                      "Barangay"
                                                  }`}
                                        </span>
                                        <span>{m.created_at}</span>
                                    </div>
                                    <div className="mt-2 whitespace-pre-wrap text-sm">
                                        {m.body}
                                    </div>
                                </div>
                            </div>
                        );
                    })}

                    {!ticket.is_closed && (
                        <form
                            onSubmit={submitReply}
                            className="mt-4 rounded-lg bg-white p-4 shadow"
                        >
                            <FormErrorSummary errors={replyForm.errors} />
                            <label className="block text-sm font-medium text-slate-700">
                                Reply as Central
                            </label>
                            <textarea
                                value={replyForm.data.body}
                                onChange={(e) =>
                                    replyForm.setData("body", e.target.value)
                                }
                                rows={4}
                                maxLength={5000}
                                className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:ring-cyan-500"
                                required
                            />
                            <FieldError message={replyForm.errors.body} />

                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <label className="text-sm text-slate-600">
                                    Set status after reply:{" "}
                                    <select
                                        value={replyForm.data.next_status}
                                        onChange={(e) =>
                                            replyForm.setData(
                                                "next_status",
                                                e.target.value
                                            )
                                        }
                                        className="rounded border border-slate-300 px-2 py-1 text-sm"
                                    >
                                        <option value="">(keep)</option>
                                        {statuses.map((s) => (
                                            <option key={s} value={s}>
                                                {labelize(s)}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <button
                                    type="submit"
                                    disabled={replyForm.processing}
                                    className="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-cyan-700 disabled:opacity-60"
                                >
                                    {replyForm.processing
                                        ? "Sending..."
                                        : "Send reply"}
                                </button>
                            </div>
                        </form>
                    )}
                </div>

                <aside className="space-y-4">
                    <div className="rounded-lg bg-white p-4 shadow">
                        <h3 className="text-sm font-semibold text-slate-700">
                            Change status
                        </h3>
                        <form
                            onSubmit={submitStatus}
                            className="mt-3 space-y-2"
                        >
                            <select
                                value={statusForm.data.status}
                                onChange={(e) =>
                                    statusForm.setData(
                                        "status",
                                        e.target.value
                                    )
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            >
                                {statuses.map((s) => (
                                    <option key={s} value={s}>
                                        {labelize(s)}
                                    </option>
                                ))}
                            </select>
                            <textarea
                                value={statusForm.data.closure_note}
                                onChange={(e) =>
                                    statusForm.setData(
                                        "closure_note",
                                        e.target.value
                                    )
                                }
                                placeholder="Optional closure note (shown when resolving/closing)"
                                rows={3}
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                maxLength={2000}
                            />
                            <FieldError message={statusForm.errors.status} />
                            <button
                                type="submit"
                                disabled={statusForm.processing}
                                className="w-full rounded-lg bg-slate-700 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                            >
                                {statusForm.processing
                                    ? "Updating..."
                                    : "Update status"}
                            </button>
                        </form>
                    </div>

                    {ticket.is_closed && (
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                            <div className="font-medium">
                                Closed on {ticket.closed_at}
                            </div>
                            {ticket.closed_by && (
                                <div className="mt-1 text-xs">
                                    by {ticket.closed_by.name}
                                </div>
                            )}
                            {ticket.closure_note && (
                                <div className="mt-2 whitespace-pre-wrap">
                                    {ticket.closure_note}
                                </div>
                            )}
                        </div>
                    )}

                    <div className="rounded-lg bg-white p-4 text-xs text-slate-500 shadow">
                        <div className="font-semibold text-slate-700">
                            Details
                        </div>
                        <dl className="mt-2 space-y-1">
                            <div className="flex justify-between">
                                <dt>Barangay</dt>
                                <dd className="font-medium text-slate-700">
                                    {ticket.tenant?.name || "—"}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Category</dt>
                                <dd className="text-slate-700">
                                    {labelize(ticket.category)}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Priority</dt>
                                <dd className="text-slate-700">
                                    {labelize(ticket.priority)}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Opened</dt>
                                <dd className="text-slate-700">
                                    {ticket.created_at}
                                </dd>
                            </div>
                            <div className="flex justify-between">
                                <dt>Last activity</dt>
                                <dd className="text-slate-700">
                                    {ticket.last_activity_at ||
                                        ticket.created_at}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </CentralLayout>
    );
}
