import { useEffect, useRef, useState } from "react";

export default function SystemUpdaterPanel({
    title = "System updater",
    description = "Downloads the latest GitHub Release asset and installs it asynchronously.",
}) {
    const [updateId, setUpdateId] = useState(null);
    const [updateStatus, setUpdateStatus] = useState(null);
    const [updateVersion, setUpdateVersion] = useState(null);
    const [updateLog, setUpdateLog] = useState("");
    const [maintenanceBypassUrl, setMaintenanceBypassUrl] = useState(null);
    const [updateError, setUpdateError] = useState(null);
    const [isTriggering, setIsTriggering] = useState(false);
    const pollRef = useRef(null);

    const fetchUpdate = async (id) => {
        const res = await window.axios.get(`/system/update/${id}`);
        setUpdateStatus(res.data?.status ?? null);
        setUpdateVersion(res.data?.version ?? null);
        setUpdateLog(res.data?.log ?? "");
        if (res.data?.maintenance_bypass_url) {
            setMaintenanceBypassUrl(res.data.maintenance_bypass_url);
        }
        return res.data;
    };

    const startPolling = (id) => {
        if (pollRef.current) window.clearInterval(pollRef.current);

        const tick = async () => {
            try {
                const data = await fetchUpdate(id);
                const status = data?.status;
                if (status === "success" || status === "failed") {
                    if (pollRef.current) {
                        window.clearInterval(pollRef.current);
                        pollRef.current = null;
                    }
                }
            } catch (e) {
                setUpdateError(
                    e?.response?.data?.message ??
                        e?.message ??
                        "Failed to fetch update status"
                );
            }
        };

        void tick();
        pollRef.current = window.setInterval(() => void tick(), 2000);
    };

    const triggerUpdate = async () => {
        setUpdateError(null);
        setIsTriggering(true);
        try {
            const res = await window.axios.post("/system/update");
            const id = res.data?.id;
            setUpdateId(id);
            setUpdateStatus(res.data?.status ?? "queued");
            if (res.data?.maintenance_bypass_url) {
                setMaintenanceBypassUrl(res.data.maintenance_bypass_url);
            }
            if (id) {
                try {
                    await fetchUpdate(id);
                } catch {
                    // First poll can fail (503, session); keep polling until status resolves.
                }
                startPolling(id);
            }
        } catch (e) {
            setUpdateError(
                e?.response?.data?.message ??
                    e?.message ??
                    "Failed to trigger update"
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
                        onClick={triggerUpdate}
                        disabled={isTriggering}
                        className="rounded-devias bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {isTriggering ? "Starting…" : "Run update"}
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

            {updateError && (
                <div className="mt-3 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {updateError}
                </div>
            )}

            {maintenanceBypassUrl && (
                <div className="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-950">
                    <p className="font-medium">
                        Maintenance mode will lock out normal pages (503). Use this
                        link once if you refresh or open another tab during the
                        update:
                    </p>
                    <p className="mt-2 break-all font-mono text-xs">
                        <a
                            href={maintenanceBypassUrl}
                            className="text-indigo-700 underline hover:text-indigo-900"
                            target="_blank"
                            rel="noreferrer"
                        >
                            {maintenanceBypassUrl}
                        </a>
                    </p>
                    <p className="mt-2 text-xs text-amber-900/80">
                        System update status requests stay allowed without this
                        link; the link restores the rest of the app in your
                        browser.
                    </p>
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
                            {updateStatus ?? "—"}
                        </p>
                    </div>
                    <div className="rounded border border-slate-200 bg-slate-50 p-3">
                        <p className="text-xs font-medium text-slate-500">
                            Version
                        </p>
                        <p className="mt-1 font-semibold text-slate-800">
                            {updateVersion ?? "—"}
                        </p>
                    </div>
                    <div className="lg:col-span-3">
                        <p className="mb-2 text-xs font-medium text-slate-500">
                            Logs
                        </p>
                        <pre className="max-h-72 overflow-auto whitespace-pre-wrap rounded border border-slate-200 bg-slate-900 p-3 text-xs text-slate-100">
{updateLog || "No logs yet."}
                        </pre>
                    </div>
                </div>
            )}
        </div>
    );
}

