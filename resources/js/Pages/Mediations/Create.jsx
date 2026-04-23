import { Link, useForm } from "@inertiajs/react";
import TenantLayout from "../Layouts/TenantLayout";
import FormErrorSummary, { FieldError } from "../../Components/FormErrorSummary";

export default function MediationsCreate({ incident, mediators }) {
    const hasMediators = Array.isArray(mediators) && mediators.length > 0;

    const { data, setData, post, processing, errors } = useForm({
        incident_id: incident?.id ?? "",
        mediator_user_id: mediators?.[0]?.id?.toString() ?? "",
        scheduled_at: new Date().toISOString().slice(0, 16),
    });

    return (
        <TenantLayout>
            <h1 className="mb-6 text-2xl font-bold text-slate-800">
                Schedule mediation — Incident{" "}
                {incident?.blotter_number ?? `#${incident?.id}`}
            </h1>

            {/* If there are no available mediators, a `required` <select> with
          no options is unsatisfiable — the user clicks "Schedule" and
          the browser silently blocks submit with no feedback. Surface
          a clear message + disable the button. */}
            {!hasMediators && (
                <div className="mb-4 max-w-md rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                    No community mediators are assigned in this barangay yet.
                    Ask a Barangay Admin to assign a mediator before scheduling.
                </div>
            )}

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post("/mediations");
                }}
                className="max-w-md space-y-4 rounded-lg bg-white p-6 shadow"
                noValidate
            >
                <FormErrorSummary errors={errors} />

                <input type="hidden" name="incident_id" value={data.incident_id} />

                <div>
                    <label
                        htmlFor="mediator_user_id"
                        className="mb-1 block text-sm font-medium text-slate-700"
                    >
                        Mediator
                    </label>
                    <select
                        id="mediator_user_id"
                        value={data.mediator_user_id}
                        onChange={(e) =>
                            setData("mediator_user_id", e.target.value)
                        }
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 disabled:bg-slate-100"
                        required
                        disabled={!hasMediators || processing}
                    >
                        {hasMediators ? (
                            mediators.map((m) => (
                                <option key={m.id} value={m.id}>
                                    {m.name}
                                </option>
                            ))
                        ) : (
                            <option value="">No mediators available</option>
                        )}
                    </select>
                    <FieldError message={errors.mediator_user_id} />
                </div>

                <div>
                    <label
                        htmlFor="scheduled_at"
                        className="mb-1 block text-sm font-medium text-slate-700"
                    >
                        Date & time
                    </label>
                    <input
                        id="scheduled_at"
                        type="datetime-local"
                        value={data.scheduled_at}
                        onChange={(e) => setData("scheduled_at", e.target.value)}
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        required
                    />
                    <FieldError message={errors.scheduled_at} />
                </div>

                <div className="flex gap-2 pt-2">
                    <button
                        type="submit"
                        disabled={processing || !hasMediators}
                        className="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? "Scheduling…" : "Schedule"}
                    </button>
                    <Link
                        href={`/incidents/${incident?.id ?? ""}`}
                        className="rounded-lg bg-slate-200 px-4 py-2 text-slate-700 hover:bg-slate-300"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </TenantLayout>
    );
}
