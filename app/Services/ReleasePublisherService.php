<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lets the central app list GitHub releases, see which ones already have a
 * release.zip asset, and trigger the build-release-asset.yml workflow for a
 * release that doesn't.
 *
 * This is the "publish" side of the updater pipeline. The "install" side is
 * SystemUpdateService, which runs on each tenant's server and downloads the
 * release.zip asset this service is responsible for producing.
 */
class ReleasePublisherService
{
    private string $owner;
    private string $repo;
    private string $assetName;
    private ?string $token;
    private string $workflowFile;

    public function __construct()
    {
        $this->owner = (string) config('system_update.github.owner', '');
        $this->repo = (string) config('system_update.github.repo', '');
        $this->assetName = (string) config('system_update.github.asset_name', 'release.zip');
        // Publishing requires contents:write + actions:write, so it uses a
        // different (stronger) token than the read-only download token.
        $this->token = config('system_update.publish.token') ?: config('system_update.github.token');
        $this->workflowFile = (string) config('system_update.publish.workflow_file', 'build-release-asset.yml');
    }

    public function isConfigured(): bool
    {
        return $this->owner !== '' && $this->repo !== '' && !empty($this->token);
    }

    /**
     * @return array<int, array{
     *   id: int,
     *   tag: string,
     *   name: string,
     *   url: string,
     *   draft: bool,
     *   prerelease: bool,
     *   published_at: ?string,
     *   has_asset: bool,
     *   asset_size: ?int,
     *   asset_url: ?string
     * }>
     */
    public function listReleases(int $perPage = 10): array
    {
        $this->assertConfigured();

        $res = $this->github()
            ->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/releases", [
                'per_page' => $perPage,
            ])
            ->throw()
            ->json();

        $out = [];
        foreach ($res as $rel) {
            $assets = $rel['assets'] ?? [];
            $asset = collect($assets)->firstWhere('name', $this->assetName);
            $out[] = [
                'id' => (int) ($rel['id'] ?? 0),
                'tag' => (string) ($rel['tag_name'] ?? ''),
                'name' => (string) ($rel['name'] ?? ($rel['tag_name'] ?? '')),
                'url' => (string) ($rel['html_url'] ?? ''),
                'draft' => (bool) ($rel['draft'] ?? false),
                'prerelease' => (bool) ($rel['prerelease'] ?? false),
                'published_at' => $rel['published_at'] ?? null,
                'has_asset' => $asset !== null,
                'asset_size' => $asset['size'] ?? null,
                'asset_url' => $asset['browser_download_url'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Creates a new GitHub Release for `tag` on the default branch. If the
     * tag doesn't exist yet, GitHub creates it pointing at `target_commitish`
     * for us — no local git operations required.
     *
     * This is the "ship a new version" entry point from the in-app UI: the
     * super-admin types a tag + release notes, we persist it on GitHub, and
     * the caller can then trigger the build-release-asset workflow to
     * attach release.zip to the freshly-created release.
     *
     * @return array{id:int,tag:string,name:string,url:string,draft:bool,prerelease:bool,published_at:?string}
     */
    public function createRelease(
        string $tag,
        string $name = '',
        string $body = '',
        bool $prerelease = false,
        bool $draft = false,
    ): array {
        $this->assertConfigured();

        $tag = trim($tag);
        if ($tag === '') {
            throw new RuntimeException('Tag is required.');
        }

        // GitHub treats tag_name as the identifier, so we refuse to proceed
        // if the tag already has a release — the caller should rebuild the
        // existing one instead of stacking duplicate releases.
        $existing = $this->github()
            ->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/releases/tags/" . rawurlencode($tag));

        if ($existing->successful()) {
            throw new RuntimeException("A release for tag '{$tag}' already exists. Use Rebuild on that release instead.");
        }

        $res = $this->github()
            ->post("https://api.github.com/repos/{$this->owner}/{$this->repo}/releases", [
                'tag_name'               => $tag,
                'target_commitish'       => $this->defaultBranch(),
                'name'                   => $name !== '' ? $name : $tag,
                'body'                   => $body,
                'draft'                  => $draft,
                'prerelease'             => $prerelease,
                'generate_release_notes' => $body === '',
            ])
            ->throw()
            ->json();

        return [
            'id'           => (int) ($res['id'] ?? 0),
            'tag'          => (string) ($res['tag_name'] ?? $tag),
            'name'         => (string) ($res['name'] ?? $tag),
            'url'          => (string) ($res['html_url'] ?? ''),
            'draft'        => (bool) ($res['draft'] ?? false),
            'prerelease'   => (bool) ($res['prerelease'] ?? false),
            'published_at' => $res['published_at'] ?? null,
        ];
    }

    /**
     * Fires the build-release-asset workflow for a specific tag.
     *
     * Returns the run (if one becomes visible quickly) so the UI can start
     * polling. GitHub's workflow_dispatch endpoint doesn't return the run id
     * directly, so we fall back to listing recent runs for that workflow.
     *
     * @return array{status: string, run: ?array<string, mixed>}
     */
    public function triggerBuild(string $tag): array
    {
        $this->assertConfigured();

        if ($tag === '') {
            throw new RuntimeException('Tag is required.');
        }

        $this->github()
            ->post("https://api.github.com/repos/{$this->owner}/{$this->repo}/actions/workflows/{$this->workflowFile}/dispatches", [
                'ref' => $this->defaultBranch(),
                'inputs' => ['tag' => $tag],
            ])
            ->throw();

        // Give GitHub ~2s to register the run so the UI has something to poll.
        usleep(2_000_000);

        return [
            'status' => 'dispatched',
            'run' => $this->findRunForTag($tag),
        ];
    }

    /**
     * Looks up the latest workflow run matching a tag input.
     *
     * @return ?array{id:int,status:string,conclusion:?string,url:string,created_at:string}
     */
    public function findRunForTag(string $tag): ?array
    {
        $this->assertConfigured();

        $runs = $this->github()
            ->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/actions/workflows/{$this->workflowFile}/runs", [
                'per_page' => 20,
                'event' => 'workflow_dispatch',
            ])
            ->throw()
            ->json('workflow_runs') ?? [];

        foreach ($runs as $run) {
            // head_branch for dispatched runs is the ref we triggered on; the
            // tag itself shows up in the display_title / name on newer APIs.
            $name = (string) ($run['display_title'] ?? $run['name'] ?? '');
            if (str_contains($name, $tag)) {
                return $this->shapeRun($run);
            }
        }

        // Fallback: newest run, best-effort.
        if (!empty($runs)) {
            return $this->shapeRun($runs[0]);
        }

        return null;
    }

    /**
     * @return ?array{id:int,status:string,conclusion:?string,url:string,created_at:string}
     */
    public function getRun(int $runId): ?array
    {
        $this->assertConfigured();

        $run = $this->github()
            ->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/actions/runs/{$runId}")
            ->throw()
            ->json();

        return $this->shapeRun($run);
    }

    /**
     * @param  array<string, mixed>  $run
     * @return array{id:int,status:string,conclusion:?string,url:string,created_at:string}
     */
    private function shapeRun(array $run): array
    {
        return [
            'id' => (int) ($run['id'] ?? 0),
            'status' => (string) ($run['status'] ?? 'unknown'),
            'conclusion' => $run['conclusion'] ?? null,
            'url' => (string) ($run['html_url'] ?? ''),
            'created_at' => (string) ($run['created_at'] ?? ''),
        ];
    }

    private function defaultBranch(): string
    {
        return (string) config('system_update.publish.branch', 'main');
    }

    private function github(): PendingRequest
    {
        $req = Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'Barangay-ReleasePublisher',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout((int) config('system_update.github.http_timeout_api', 120))
            ->connectTimeout(30);

        $caPath = (string) config('system_update.github.curl_ca_bundle', '');
        if ($caPath !== '' && is_readable($caPath)) {
            $req = $req->withOptions(['verify' => $caPath]);
        } elseif (!filter_var(config('system_update.github.verify_ssl'), FILTER_VALIDATE_BOOLEAN)) {
            $req = $req->withOptions(['verify' => false]);
        }

        if ($this->token) {
            $req = $req->withToken($this->token);
        }

        return $req;
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException(
                'Release publisher is not configured. Set UPDATE_GITHUB_OWNER, UPDATE_GITHUB_REPO, and UPDATE_GITHUB_PUBLISH_TOKEN.'
            );
        }
    }
}
