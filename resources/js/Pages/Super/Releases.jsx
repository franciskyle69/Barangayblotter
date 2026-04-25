import { useEffect, useRef, useState } from "react";
import CentralLayout from "../Layouts/CentralLayout";

function formatBytes(bytes) {
    if (!bytes && bytes !== 0) return "—";
    const units = ["B", "KB", "MB", "GB"];
    let i = 0;
    let n = bytes;
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024;
        i++;
    }
    return `${n.toFixed(1)} ${units[i]}`;
}

function StatusPill({ release, runState }) {
    if (release.has_asset) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                <span className="size-1.5 rounded-full bg-emerald-500" />
                Published ({formatBytes(release.asset_size)})
            </span>
        );
    }

    if (runState?.status === "in_progress" || runState?.status === "queued") {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                <span className="size-1.5 animate-pulse rounded-full bg-blue-500" />
                {runState.status === "queued" ? "Queued" : "Building…"}
            </span>
        );
    }

    if (runState?.conclusion === "failure") {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                <span className="size-1.5 rounded-full bg-rose-500" />
                Build failed
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">
            <span className="size-1.5 rounded-full bg-amber-500" />
            No asset yet
        </span>
    );
}

function CreateReleasePanel({ disabled }) {
    // Suggest the next minor bump off the last seen release via a sensible
    // default. Empty is fine — the user will overwrite it anyway.
    const [open, setOpen] = useState(false);
    const [tag, setTag] = useState("");
    const [name, setName] = useState("");
    const [body, setBody] = useState("");
    const [prerelease, setPrerelease] = useState(false);
    const [autoBuild, setAutoBuild] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const reset = () => {
        setTag("");
        setName("");
        setBody("");
        setPrerelease(false);
        setAutoBuild(true);
        setError(null);
        setSuccess(null);
    };

    const submit = async (e) => {
        e.preventDefault();
        setError(null);
        setSuccess(null);
        setSubmitting(true);
        try {
            const res = await window.axios.post("/super/releases/create", {
                tag: tag.trim(),
                name: name.trim(),
                body,
                prerelease,
                auto_build: autoBuild,
            });
            setSuccess(
                autoBuild
                    ? `Created ${res.data.release.tag} and started the build. Reloading…`
                    : `Created ${res.data.release.tag} on GitHub.`
            );
            window.setTimeout(() => window.location.reload(), 1200);
        } catch (e) {
            const data = e?.response?.data;
            const msg =
                data?.errors
                    ? Object.values(data.errors).flat().join(" ")
                    : data?.message || e?.message || "Failed to create release";
            setError(msg);
        } finally {
            setSubmitting(false);
        }
    };

    if (!open) {
        return (
            <div className="flex items-center justify-between rounded-devias border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600">
                <span>
                    Ready to ship a new version? Create a GitHub release and
                    build <code className="rounded bg-slate-100 px-1">release.zip</code>{" "}
                    in one step.
                </span>
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    disabled={disabled}
                    className="rounded-devias bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    New Release
                </button>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            className="space-y-3 rounded-devias border border-slate-200 bg-white p-4 shadow-sm"
        >
            <div className="flex items-center justify-between">
                <h3 className="text-base font-semibold text-slate-900">
                    Create new release
                </h3>
                <button
                    type="button"
                    onClick={() => {
                        setOpen(false);
                        reset();
                    }}
                    className="text-xs text-slate-500 hover:text-slate-700"
                >
                    Cancel
                </button>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label className="text-sm">
                    <span className="mb-1 block font-medium text-slate-700">
                        Tag <span className="text-rose-500">*</span>
                    </span>
                    <input
                        type="text"
                        value={tag}
                        onChange={(e) => setTag(e.target.value)}
                        placeholder="v1.3.0"
                        required
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <span className="mt-1 block text-xs text-slate-500">
                        Must match semver (e.g. v1.3.0 or v1.3.0-rc.1).
                    </span>
                </label>

                <label className="text-sm">
                    <span className="mb-1 block font-medium text-slate-700">
                        Release name
                    </span>
                    <input
                        type="text"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        placeholder="(defaults to tag)"
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </label>
            </div>

            <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">
                    Release notes
                </span>
                <textarea
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    rows={5}
                    placeholder="Leave blank to let GitHub auto-generate from merged PRs since the last tag."
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    maxLength={20000}
                />
            </label>

            <div className="flex flex-wrap items-center gap-4 text-sm text-slate-700">
                <label className="inline-flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={prerelease}
                        onChange={(e) => setPrerelease(e.target.checked)}
                        className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    Mark as pre-release
                </label>
                <label className="inline-flex items-center gap-2">
                    <input
                        type="checkbox"
                        checked={autoBuild}
                        onChange={(e) => setAutoBuild(e.target.checked)}
                        className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    Build <code className="rounded bg-slate-100 px-1">release.zip</code>{" "}
                    immediately
                </label>
            </div>

            {error && (
                <div className="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {error}
                </div>
            )}
            {success && (
                <div className="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                    {success}
                </div>
            )}

            <div className="flex justify-end">
                <button
                    type="submit"
                    disabled={submitting || disabled || !tag.trim()}
                    className="rounded-devias bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {submitting
                        ? "Creating…"
                        : autoBuild
                          ? "Create & Build"
                          : "Create Release"}
                </button>
            </div>
        </form>
    );
}

function ReleaseRow({ release }) {
    const [runState, setRunState] = useState(null);
    const [isTriggering, setIsTriggering] = useState(false);
    const [rowError, setRowError] = useState(null);
    const pollRef = useRef(null);

    const stopPolling = () => {
        if (pollRef.current) {
            window.clearInterval(pollRef.current);
            pollRef.current = null;
        }
    };

    const pollStatus = async (runId) => {
        try {
            const res = await window.axios.get("/super/releases/status", {
                params: runId ? { run_id: runId } : { tag: release.tag },
            });
            const run = res.data?.run;
            setRunState(run);
            if (
                run &&
                run.status === "completed" &&
                (run.conclusion === "success" || run.conclusion === "failure")
            ) {
                stopPolling();
                // Reload page data so `has_asset` refreshes from GitHub.
                if (run.conclusion === "success") {
                    window.setTimeout(() => window.location.reload(), 800);
                }
            }
        } catch (e) {
            setRowError(
                e?.response?.data?.message ??
                    e?.message ??
                    "Failed to fetch status"
            );
        }
    };

    const startPolling = (runId) => {
        stopPolling();
        void pollStatus(runId);
        pollRef.current = window.setInterval(
            () => void pollStatus(runId),
            5000
        );
    };

    const triggerBuild = async () => {
        setRowError(null);
        setIsTriggering(true);
        try {
            const res = await window.axios.post("/super/releases/publish", {
                tag: release.tag,
            });
            const run = res.data?.run;
            setRunState(run ?? { status: "queued", conclusion: null });
            startPolling(run?.id);
        } catch (e) {
            setRowError(
                e?.response?.data?.message ??
                    e?.message ??
                    "Failed to trigger build"
            );
        } finally {
            setIsTriggering(false);
        }
    };

    useEffect(() => stopPolling, []);

    const isBusy =
        isTriggering ||
        runState?.status === "in_progress" ||
        runState?.status === "queued";

    return (
        <div className="rounded-devias border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <h3 className="text-base font-semibold text-slate-900">
                            {release.name || release.tag}
                        </h3>
                        {release.prerelease && (
                            <span className="rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-purple-700">
                                pre-release
                            </span>
                        )}
                        {release.draft && (
                            <span className="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                                draft
                            </span>
                        )}
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        {release.published_at
                            ? new Date(release.published_at).toLocaleString()
                            : "Not yet published"}
                        {release.url && (
                            <>
                                {" · "}
                                <a
                                    className="text-indigo-600 hover:underline"
                                    href={release.url}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    View on GitHub
                                </a>
                            </>
                        )}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <StatusPill release={release} runState={runState} />
                    {release.has_asset ? (
                        <button
                            type="button"
                            onClick={triggerBuild}
                            disabled={isBusy}
                            className="rounded-devias border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                            title="Rebuild and replace the attached release.zip"
                        >
                            {isBusy ? "Rebuilding…" : "Rebuild"}
                        </button>
                    ) : (
                        <button
                            type="button"
                            onClick={triggerBuild}
                            disabled={isBusy}
                            className="rounded-devias bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {isBusy ? "Publishing…" : "Publish Release"}
                        </button>
                    )}
                </div>
            </div>

            {rowError && (
                <div className="mt-3 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {rowError}
                </div>
            )}

            {runState && (
                <div className="mt-3 flex flex-wrap items-center gap-3 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    <span>
                        <span className="font-medium text-slate-800">
                            Workflow status:
                        </span>{" "}
                        {runState.status}
                        {runState.conclusion ? ` · ${runState.conclusion}` : ""}
                    </span>
                    {runState.url && (
                        <a
                            href={runState.url}
                            target="_blank"
                            rel="noreferrer"
                            className="text-indigo-600 hover:underline"
                        >
                            View run logs →
                        </a>
                    )}
                </div>
            )}
        </div>
    );
}

export default function SuperReleases({
    configured,
    releases,
    error,
    asset_name,
}) {
    return (
        <CentralLayout>
            <div className="mx-auto max-w-5xl space-y-6">
                <div>
                    <p className="text-sm text-slate-500">Pages / Releases</p>
                    <h1 className="text-3xl font-bold text-slate-900">
                        Publish Releases
                    </h1>
                    <p className="mt-1 text-sm text-slate-600">
                        One-click build and upload of{" "}
                        <code className="rounded bg-slate-100 px-1.5 py-0.5 text-xs">
                            {asset_name}
                        </code>{" "}
                        to your GitHub releases. Tenants can then install it
                        from their own Settings page.
                    </p>
                </div>

                {!configured && (
                    <div className="rounded-devias border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p className="font-semibold">
                            Release publisher not configured
                        </p>
                        <p className="mt-1">
                            Set the following in your{" "}
                            <code className="rounded bg-white/70 px-1 py-0.5 text-xs">
                                .env
                            </code>{" "}
                            and restart the app:
                        </p>
                        <ul className="mt-2 list-inside list-disc space-y-0.5 font-mono text-xs">
                            <li>UPDATE_GITHUB_OWNER</li>
                            <li>UPDATE_GITHUB_REPO</li>
                            <li>
                                UPDATE_GITHUB_PUBLISH_TOKEN (PAT with
                                contents:write + actions:write)
                            </li>
                        </ul>
                    </div>
                )}

                {error && (
                    <div className="rounded-devias border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {error}
                    </div>
                )}

                {configured && !error && (
                    <CreateReleasePanel disabled={!configured} />
                )}

                {configured && !error && releases.length === 0 && (
                    <div className="rounded-devias border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">
                        No releases found on GitHub yet. Merge a release-please
                        PR on your default branch, then come back here.
                    </div>
                )}

                {releases.length > 0 && (
                    <div className="space-y-3">
                        {releases.map((release) => (
                            <ReleaseRow key={release.id} release={release} />
                        ))}
                    </div>
                )}

                <div className="rounded-devias border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                    <p className="font-semibold text-slate-700">How it works</p>
                    <ol className="mt-1 list-inside list-decimal space-y-0.5">
                        <li>
                            Either merge a release-please PR, or use{" "}
                            <strong>New Release</strong> above to create a tag
                            directly from the app.
                        </li>
                        <li>
                            Click <strong>Publish Release</strong> (or let{" "}
                            <em>Create &amp; Build</em> do it for you) → central
                            app triggers the <code>build-release-asset</code>{" "}
                            GitHub Action.
                        </li>
                        <li>
                            The action builds composer+vite assets, zips them,
                            and attaches{" "}
                            <code className="rounded bg-white px-1 py-0.5">
                                {asset_name}
                            </code>{" "}
                            to the release.
                        </li>
                        <li>
                            Tenants click <em>Run update</em> on their own
                            Settings page → the existing System Updater pulls
                            and installs it. Fully hands-off.
                        </li>
                    </ol>
                </div>
            </div>
        </CentralLayout>
    );
}
