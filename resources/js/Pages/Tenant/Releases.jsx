import TenantLayout from "../Layouts/TenantLayout";

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

function StatusPill({ release }) {
    if (release.has_asset) {
        return (
            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                <span className="size-1.5 rounded-full bg-emerald-500" />
                Asset ready ({formatBytes(release.asset_size)})
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800">
            <span className="size-1.5 rounded-full bg-amber-500" />
            No asset
        </span>
    );
}

export default function TenantReleases({ configured, releases, error, asset_name }) {
    const items = Array.isArray(releases) ? releases : [];

    return (
        <TenantLayout>
            <div className="space-y-6">
                <div>
                    <p className="text-sm text-slate-500">Pages / Releases</p>
                    <h1 className="text-2xl font-bold text-slate-800">
                        GitHub Releases
                    </h1>
                    <p className="mt-1 text-sm text-slate-600">
                        Read-only list of versions published on GitHub. This does
                        not update the system by itself.
                    </p>
                </div>

                {!configured && (
                    <div className="rounded-devias border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p className="font-semibold">Not configured</p>
                        <p className="mt-1">
                            Set <code className="rounded bg-white/70 px-1 py-0.5 text-xs">UPDATE_GITHUB_OWNER</code>{" "}
                            and <code className="rounded bg-white/70 px-1 py-0.5 text-xs">UPDATE_GITHUB_REPO</code>{" "}
                            in <code className="rounded bg-white/70 px-1 py-0.5 text-xs">.env</code>.
                        </p>
                    </div>
                )}

                {error && (
                    <div className="rounded-devias border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {error}
                    </div>
                )}

                {configured && !error && items.length === 0 && (
                    <div className="rounded-devias border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">
                        No releases found on GitHub yet.
                    </div>
                )}

                {items.length > 0 && (
                    <div className="space-y-3">
                        {items.map((release) => (
                            <div
                                key={release.id}
                                className="rounded-devias border border-slate-200 bg-white p-4 shadow-sm"
                            >
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
                                                ? new Date(
                                                      release.published_at,
                                                  ).toLocaleString()
                                                : "Not yet published"}
                                            {release.url && (
                                                <>
                                                    {" · "}
                                                    <a
                                                        className="text-emerald-700 hover:underline"
                                                        href={release.url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        View on GitHub
                                                    </a>
                                                </>
                                            )}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500">
                                            Asset expected:{" "}
                                            <code className="rounded bg-slate-100 px-1 py-0.5">
                                                {asset_name}
                                            </code>
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <StatusPill release={release} />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </TenantLayout>
    );
}

