import { router } from "@inertiajs/react";
import { useMemo, useState } from "react";
import Swal from "sweetalert2";
import CentralLayout from "../Layouts/CentralLayout";

export default function BackupRestore({ backups, backupDirectory }) {
    const history = useMemo(
        () => (Array.isArray(backups) ? backups : []),
        [backups],
    );
    const [file, setFile] = useState(null);
    const [confirmation, setConfirmation] = useState("");
    const [creating, setCreating] = useState(false);
    const [restoring, setRestoring] = useState(false);

    const formatNumber = (value) => {
        if (typeof value !== "number") {
            return "-";
        }

        return value.toLocaleString("en-US");
    };

    const handleCreateBackup = async () => {
        const result = await Swal.fire({
            title: "Create database backup?",
            text: "This will snapshot central data and all tenant databases.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Create Backup",
        });

        if (!result.isConfirmed) {
            return;
        }

        router.post(
            "/super/backup-restore/create",
            {},
            {
                preserveScroll: true,
                onStart: () => setCreating(true),
                onFinish: () => setCreating(false),
            },
        );
    };

    const handleRestoreUpload = async (event) => {
        event.preventDefault();

        if (!file) {
            Swal.fire({
                icon: "warning",
                title: "Select a backup file first.",
            });
            return;
        }

        if (confirmation !== "RESTORE") {
            Swal.fire({
                icon: "warning",
                title: "Type RESTORE exactly to continue.",
            });
            return;
        }

        const result = await Swal.fire({
            title: "Restore uploaded backup?",
            text: "Current data will be replaced with the uploaded snapshot.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Restore Now",
            confirmButtonColor: "#dc2626",
        });

        if (!result.isConfirmed) {
            return;
        }

        router.post(
            "/super/backup-restore/restore-upload",
            {
                confirmation,
                backup_file: file,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => setRestoring(true),
                onFinish: () => {
                    setRestoring(false);
                    setFile(null);
                    setConfirmation("");
                },
            },
        );
    };

    const handleRestoreFromHistory = async (filename) => {
        const result = await Swal.fire({
            title: `Restore ${filename}?`,
            text: "This will replace current data with this backup snapshot.",
            icon: "warning",
            input: "text",
            inputLabel: "Type RESTORE to continue",
            inputPlaceholder: "RESTORE",
            showCancelButton: true,
            confirmButtonText: "Restore Backup",
            confirmButtonColor: "#dc2626",
            preConfirm: (value) => {
                if (value !== "RESTORE") {
                    Swal.showValidationMessage(
                        "You must type RESTORE exactly.",
                    );
                    return false;
                }
                return value;
            },
        });

        if (!result.isConfirmed) {
            return;
        }

        router.post(
            `/super/backup-restore/restore/${encodeURIComponent(filename)}`,
            {
                confirmation: "RESTORE",
            },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <CentralLayout>
            <div className="mx-auto max-w-6xl space-y-6">
                <div>
                    <p className="text-sm text-slate-500">
                        Pages / Backup &amp; Restore
                    </p>
                    <h1 className="text-3xl font-bold text-slate-900">
                        Backup &amp; Restore
                    </h1>
                </div>

                <section className="rounded-2xl border border-slate-200 bg-slate-950 p-6 text-slate-100 shadow-xl">
                    <h2 className="text-2xl font-semibold">
                        Backup &amp; Restore
                    </h2>
                    <p className="mt-2 text-slate-300">
                        Save or restore a full snapshot of central data and all
                        tenant databases.
                    </p>

                    <div className="mt-5 rounded-xl border border-amber-300 bg-amber-100 px-5 py-4 text-amber-900">
                        <p className="text-lg font-semibold">
                            Important Information
                        </p>
                        <p className="mt-1 text-sm">
                            Restoring a backup will replace all current data.
                            Create a fresh backup before restoring.
                        </p>
                    </div>

                    <div className="mt-6 grid gap-6 lg:grid-cols-2">
                        <div className="rounded-xl border border-blue-900/60 bg-slate-900 p-5">
                            <h3 className="text-xl font-semibold text-slate-100">
                                Create New Backup
                            </h3>
                            <p className="mt-2 text-sm text-slate-300">
                                Creates a full JSON snapshot and stores it in:
                            </p>
                            <p className="mt-1 inline-block rounded bg-slate-800 px-2 py-1 text-xs text-slate-300">
                                {backupDirectory}
                            </p>

                            <button
                                type="button"
                                onClick={handleCreateBackup}
                                disabled={creating}
                                className="mt-4 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {creating
                                    ? "Creating backup..."
                                    : "Create Backup Now"}
                            </button>
                        </div>

                        <form
                            onSubmit={handleRestoreUpload}
                            className="rounded-xl border border-red-900/60 bg-slate-900 p-5"
                        >
                            <h3 className="text-xl font-semibold text-slate-100">
                                Upload &amp; Restore
                            </h3>
                            <p className="mt-2 text-sm text-slate-300">
                                Upload a previously generated JSON backup file
                                and restore it.
                            </p>

                            <input
                                type="file"
                                accept=".json,application/json,text/plain"
                                onChange={(e) =>
                                    setFile(e.target.files?.[0] || null)
                                }
                                className="mt-3 block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
                            />

                            <input
                                type="text"
                                value={confirmation}
                                onChange={(e) =>
                                    setConfirmation(e.target.value)
                                }
                                placeholder="Type RESTORE to confirm"
                                className="mt-3 block w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100"
                            />

                            <button
                                type="submit"
                                disabled={restoring}
                                className="mt-4 rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70"
                            >
                                {restoring
                                    ? "Restoring backup..."
                                    : "Upload & Restore"}
                            </button>
                        </form>
                    </div>

                    <div className="mt-8 rounded-xl border border-slate-700 bg-slate-900 p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-xl font-semibold text-slate-100">
                                Backup History
                            </h3>
                            <button
                                type="button"
                                onClick={() =>
                                    router.get(
                                        "/super/backup-restore",
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                className="rounded-lg border border-slate-500 px-3 py-1.5 text-sm font-medium text-slate-200 hover:bg-slate-800"
                            >
                                Refresh
                            </button>
                        </div>

                        {history.length === 0 ? (
                            <p className="py-8 text-center text-slate-400">
                                No backups yet. Create one above.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-800">
                                    <thead>
                                        <tr className="text-left text-xs uppercase tracking-wide text-slate-400">
                                            <th className="px-3 py-2">File</th>
                                            <th className="px-3 py-2">Size</th>
                                            <th className="px-3 py-2">
                                                Generated
                                            </th>
                                            <th className="px-3 py-2">
                                                Snapshot Info
                                            </th>
                                            <th className="px-3 py-2">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-800 text-sm text-slate-200">
                                        {history.map((item) => (
                                            <tr key={item.filename}>
                                                <td className="px-3 py-2">
                                                    <div className="font-mono text-xs text-slate-200">
                                                        {item.filename}
                                                    </div>
                                                    {item.summary_label && (
                                                        <div className="mt-1 text-xs text-slate-400">
                                                            {item.summary_label}
                                                        </div>
                                                    )}
                                                    {item.parse_error && (
                                                        <div className="mt-1 text-xs text-amber-300">
                                                            Could not parse
                                                            backup metadata.
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {item.size_human}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <div>
                                                        {item.generated_at_human ||
                                                            item.last_modified_human}
                                                    </div>
                                                    <div className="text-xs text-slate-500">
                                                        Saved:{" "}
                                                        {
                                                            item.last_modified_human
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-3 py-2 text-xs text-slate-300">
                                                    <div>
                                                        Central:{" "}
                                                        {formatNumber(
                                                            item.central_table_count,
                                                        )}{" "}
                                                        tables /{" "}
                                                        {formatNumber(
                                                            item.central_row_count,
                                                        )}{" "}
                                                        rows
                                                    </div>
                                                    <div className="mt-1">
                                                        Tenants:{" "}
                                                        {formatNumber(
                                                            item.tenant_count,
                                                        )}{" "}
                                                        DBs /{" "}
                                                        {formatNumber(
                                                            item.tenant_table_count,
                                                        )}{" "}
                                                        tables /{" "}
                                                        {formatNumber(
                                                            item.tenant_row_count,
                                                        )}{" "}
                                                        rows
                                                    </div>
                                                    <div className="mt-1 text-slate-500">
                                                        v
                                                        {item.backup_version ??
                                                            "-"}{" "}
                                                        ·{" "}
                                                        {item.central_driver ||
                                                            "-"}{" "}
                                                        · {item.app_name || "-"}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-2">
                                                    <div className="flex gap-3">
                                                        <a
                                                            href={`/super/backup-restore/download/${encodeURIComponent(item.filename)}`}
                                                            className="text-cyan-300 hover:text-cyan-200 hover:underline"
                                                        >
                                                            Download
                                                        </a>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                handleRestoreFromHistory(
                                                                    item.filename,
                                                                )
                                                            }
                                                            className="text-rose-300 hover:text-rose-200 hover:underline"
                                                        >
                                                            Restore
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </CentralLayout>
    );
}
