<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GithubReleaseReaderService
{
    private string $owner;
    private string $repo;
    private string $assetName;
    private ?string $token;

    public function __construct()
    {
        $this->owner = (string) config('system_update.github.owner', '');
        $this->repo = (string) config('system_update.github.repo', '');
        $this->assetName = (string) config('system_update.github.asset_name', 'release.zip');
        // Read-only token is optional for public repos; helps with rate limits.
        $this->token = config('system_update.github.token') ?: null;
    }

    public function isConfigured(): bool
    {
        return $this->owner !== '' && $this->repo !== '';
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
     *
     * @throws RequestException
     */
    public function listReleases(int $perPage = 10): array
    {
        $res = $this->github()
            ->get("https://api.github.com/repos/{$this->owner}/{$this->repo}/releases", [
                'per_page' => $perPage,
            ])
            ->throw()
            ->json();

        $out = [];
        foreach ((array) $res as $rel) {
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

    private function github(): PendingRequest
    {
        $req = Http::acceptJson()
            ->withHeaders([
                'User-Agent' => 'Barangay-ReleaseReader',
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
}

