import { Link, useForm } from "@inertiajs/react";
import TenantLayout from "../Layouts/TenantLayout";
import FormErrorSummary, {
    FieldError,
} from "../../Components/FormErrorSummary";

const labelize = (v) =>
    v
        .split("_")
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(" ");

export default function SupportCreate({ categories, priorities }) {
    const { data, setData, post, processing, errors } = useForm({
        subject: "",
        category: "question",
        priority: "normal",
        body: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/support");
    };

    return (
        <TenantLayout>
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-slate-800">
                        New Support Ticket
                    </h1>
                    <p className="text-sm text-slate-500">
                        Describe your issue and our central team will pick it
                        up.
                    </p>
                </div>
                <Link
                    href="/support"
                    className="text-sm font-medium text-slate-600 hover:text-slate-800"
                >
                    ← Back to tickets
                </Link>
            </div>

            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow"
            >
                <FormErrorSummary errors={errors} />

                <div>
                    <label className="block text-sm font-medium text-slate-700">
                        Subject
                    </label>
                    <input
                        type="text"
                        value={data.subject}
                        onChange={(e) => setData("subject", e.target.value)}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        maxLength={255}
                        required
                        autoFocus
                    />
                    <FieldError message={errors.subject} />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label className="block text-sm font-medium text-slate-700">
                            Category
                        </label>
                        <select
                            value={data.category}
                            onChange={(e) =>
                                setData("category", e.target.value)
                            }
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            {categories.map((c) => (
                                <option key={c} value={c}>
                                    {labelize(c)}
                                </option>
                            ))}
                        </select>
                        <FieldError message={errors.category} />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700">
                            Priority
                        </label>
                        <select
                            value={data.priority}
                            onChange={(e) =>
                                setData("priority", e.target.value)
                            }
                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                            {priorities.map((p) => (
                                <option key={p} value={p}>
                                    {labelize(p)}
                                </option>
                            ))}
                        </select>
                        <FieldError message={errors.priority} />
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium text-slate-700">
                        Describe your issue
                    </label>
                    <textarea
                        value={data.body}
                        onChange={(e) => setData("body", e.target.value)}
                        rows={7}
                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                        maxLength={5000}
                        required
                        placeholder="Please include steps, screenshots (describe them), or any error messages you've seen."
                    />
                    <div className="mt-1 flex items-center justify-between">
                        <FieldError message={errors.body} />
                        <span className="text-xs text-slate-400">
                            {data.body.length}/5000
                        </span>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-2">
                    <Link
                        href="/support"
                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 disabled:opacity-60"
                    >
                        {processing ? "Submitting..." : "Submit Ticket"}
                    </button>
                </div>
            </form>
        </TenantLayout>
    );
}
