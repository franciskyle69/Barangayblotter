import { Link, useForm } from "@inertiajs/react";
import TenantLayout from "../Layouts/TenantLayout";
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

export default function SupportShow({ ticket }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        body: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(`/support/${ticket.id}/reply`, {
            onSuccess: () => reset("body"),
            preserveScroll: true,
        });
    };

    return (
        <TenantLayout>
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div className="text-sm text-slate-500">
                        <Link
                            href="/support"
                            className="hover:text-slate-700"
                        >
                            Support
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
                    </div>
                </div>
                <div className="text-right text-xs text-slate-500">
                    <div>Opened by {ticket.opened_by_name || "—"}</div>
                    <div>on {ticket.created_at}</div>
                </div>
            </div>

            {ticket.is_closed && (
                <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    <div className="font-medium">
                        This ticket is {labelize(ticket.status).toLowerCase()}
                        {ticket.closed_at ? ` on ${ticket.closed_at}` : ""}.
                    </div>
                    {ticket.closure_note && (
                        <div className="mt-1 whitespace-pre-wrap">
                            {ticket.closure_note}
                        </div>
                    )}
                </div>
            )}

            <div className="space-y-3">
                {ticket.messages.map((m) => {
                    const isTenant = m.author_scope === "tenant";
                    return (
                        <div
                            key={m.id}
                            className={`flex ${
                                isTenant ? "justify-end" : "justify-start"
                            }`}
                        >
                            <div
                                className={`max-w-[85%] rounded-lg p-4 shadow-sm ${
                                    isTenant
                                        ? "bg-emerald-50 text-slate-800"
                                        : "bg-white text-slate-800"
                                }`}
                            >
                                <div className="flex items-center justify-between gap-2 text-xs text-slate-500">
                                    <span className="font-medium">
                                        {isTenant
                                            ? m.author_name || "Barangay"
                                            : `${
                                                  m.author_name || "Central"
                                              } (Central)`}
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
            </div>

            {!ticket.is_closed && (
                <form
                    onSubmit={submit}
                    className="mt-6 rounded-lg bg-white p-4 shadow"
                >
                    <FormErrorSummary errors={errors} />
                    <label className="block text-sm font-medium text-slate-700">
                        Add a reply
                    </label>
                    <textarea
                        value={data.body}
                        onChange={(e) => setData("body", e.target.value)}
                        rows={4}
                        maxLength={5000}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        required
                    />
                    <div className="mt-1 flex items-center justify-between">
                        <FieldError message={errors.body} />
                        <span className="text-xs text-slate-400">
                            {data.body.length}/5000
                        </span>
                    </div>
                    <div className="mt-3 text-right">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60"
                        >
                            {processing ? "Sending..." : "Send reply"}
                        </button>
                    </div>
                </form>
            )}
        </TenantLayout>
    );
}
