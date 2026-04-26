<?php

namespace App\Http\Controllers;

use App\Services\GithubReleaseReaderService;
use Illuminate\Http\Client\RequestException;
use Inertia\Inertia;
use Inertia\Response;

class TenantReleasesController extends Controller
{
    public function index(GithubReleaseReaderService $reader): Response
    {
        $configured = $reader->isConfigured();
        $releases = [];
        $error = null;

        if ($configured) {
            try {
                $releases = $reader->listReleases(15);
            } catch (RequestException $e) {
                $error = 'GitHub API error: ' . $e->getMessage();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('Tenant/Releases', [
            'configured' => $configured,
            'releases' => $releases,
            'error' => $error,
            'asset_name' => (string) config('system_update.github.asset_name', 'release.zip'),
        ]);
    }
}

