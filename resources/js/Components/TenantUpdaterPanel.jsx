import { useEffect, useRef, useState } from "react";

export default function TenantUpdaterPanel({
    title = "Tenant updater",
    description = "Runs tenant database migrations for this barangay only.",
}) {
    const [updateId, setUpdateId] = useState(null);
    const [status, setStatus] = useState(null);
    const [log, setLog] = useState("");
    const [error, setError] = useState(null);
    const [isTriggering, setIsTriggering] = useState(false);
    const pollRef = useRef(null);

    const fetchUpdate = async (id) => {
        const res = await window.axios.get(`/tenant/update/${id}`);
        setStatus(res.data?.status ?? null);
        setLog(res.data?.log ?? "");
        return res.data;
    };

    const startPolling = (id) => {
        if (pollRef.current) window.clearInterval(pollRef.current);

        const tick = async () => {
            try {
                const data = await fetchUpdate(id);
                const next = data?.status;
                if (next === "success" || next === "failed") {
                    if (pollRef.current) {
                        window.clearInterval(pollRef.current);
                        pollRef.current = null;
                    }
                }
            } catch (e) {
                setError(
                    e?.response?.data?.message ??
                        e?.message ??
                        "Failed to fetch update status",
                );
            }
        };

        void tick();
        pollRef.current = window.setInterval(() => void tick(), 2000);
    };

    const trigger = async () => {
        setError(null);
        setIsTriggering(true);
        try {
            const res = await window.axios.post("/tenant/update");
            const id = res.data?.id;
            setUpdateId(id);
            setStatus(res.data?.status ?? "queued");
            if (id) {
                startPolling(id);
            }
        } catch (e) {
            setError(
                e?.response?.data?.message ??
                    e?.message ??
                    "Failed to trigger tenant update",
            );
        } finally {
            setIsTriggering(false);
        }
    };

    useEffect(() => {
        return () => {
            if (pollRef.current) window.clearInterval(pollRef.current);
        };
    }, []);

    return (
        <div className="rounded-devias border border-slate-200/80 bg-white p-4 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="font-semibold text-slate-800">{title}</h2>
                    <p className="text-sm text-slate-500">{description}</p>
                </div>
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={trigger}
                        disabled={isTriggering}
                        className="rounded-devias bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isTriggering ? "Starting…" : "Run tenant update"}
                    </button>
                    {updateId && (
                        <button
                            type="button"
                            onClick={() => fetchUpdate(updateId)}
                            className="rounded-devias border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Refresh
                        </button>
                    )}
                </div>
            </div>

            {error && (
                <div className="mt-3 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {error}
                </div>
            )}

            {updateId && (
                <div className="mt-4 grid gap-3 lg:grid-cols-3">
                    <div className="rounded border border-slate-200 bg-slate-50 p-3">
                        <p className="text-xs font-medium text-slate-500">
                            Update ID
                        </p>
                        <p className="mt-1 font-semibold text-slate-800">
                            {updateId}
                        </p>
                    </div>
                    <div className="rounded border border-slate-200 bg-slate-50 p-3">
                        <p className="text-xs font-medium text-slate-500">
                            Status
                        </p>
                        <p className="mt-1 font-semibold text-slate-800">
                            {status ?? "—"}
                        </p>
                    </div>
                    <div className="rounded border border-slate-200 bg-slate-50 p-3">
                        <p className="text-xs font-medium text-slate-500">
                            Scope
                        </p>
                        <p className="mt-1 font-semibold text-slate-800">
                            This tenant only
                        </p>
                    </div>
                    <div className="lg:col-span-3">
                        <p className="mb-2 text-xs font-medium text-slate-500">
                            Logs
                        </p>
                        <pre className="max-h-72 overflow-auto whitespace-pre-wrap rounded border border-slate-200 bg-slate-900 p-3 text-xs text-slate-100">
{log || "No logs yet."}
                        </pre>
                    </div>
                </div>
            )}
        </div>
    );
}

