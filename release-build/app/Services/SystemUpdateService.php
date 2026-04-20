<?php

namespace App\Services;

use App\Models\SystemUpdate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class SystemUpdateService
{
    private string $basePath;
    private string $workDir;
    private string $backupDir;
    private string $baseConnection;
    private string $centralConnection;

    public function __construct()
    {
        $this->basePath = base_path();
        $this->workDir = storage_path('app/system-updates/work');
        $this->backupDir = storage_path('app/system-updates/backups');
        $this->baseConnection = (string) config('database.default', 'sqlite');
        $this->centralConnection = (string) config('tenancy.central_connection', 'central');
    }

    public function run(SystemUpdate $update): void
    {
        $this->ensureDirs();

        // Take a filesystem backup before maintenance mode so rollback has something
        // even if `down` fails early.
        $backupPath = $this->backupCurrentSystem($update);

        $update->appendLog('Enabling maintenance mode...');
        $down = ['down'];
        if ($update->maintenance_bypass_secret) {
            $down[] = '--secret=' . $update->maintenance_bypass_secret;
            if ($url = $update->maintenanceBypassUrl()) {
                $update->appendLog('If this page shows Service Unavailable after a refresh, open: ' . $url);
            }
        }
        try {
            $this->artisanCentral($update, $down);
        } catch (Throwable $e) {
            $update->appendLog('WARNING: could not enable maintenance mode: ' . trim($e->getMessage()));
            $update->appendLog('Continuing without maintenance mode. Users may hit transient errors while files swap.');
        }

        try {
            [$tag, $zipPath] = $this->downloadLatestReleaseZip($update);
            $update->version = $tag;
            $update->save();

            $extractPath = $this->extractZip($update, $zipPath);

            $update->appendLog('Overwriting application files (excluding .env, storage, vendor)...');
            $this->overwriteApplication($update, $extractPath);

            $update->appendLog('Running composer install...');
            $this->runProcess($update, ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'], $this->basePath, 3600);

            $this->clearApplicationCaches($update);

            $update->appendLog('Running npm install + build...');
            $this->runProcess($update, ['npm', 'ci'], $this->basePath, 3600);
            $this->runProcess($update, ['npm', 'run', 'build'], $this->basePath, 3600);

            if (config('system_update.allow_app_key_regen')) {
                $update->appendLog('Generating new APP_KEY (WARNING: breaks encrypted data) ...');
                $this->artisanCentral($update, ['key:generate', '--force']);
            } else {
                $update->appendLog('Skipping APP_KEY regeneration (recommended).');
            }

            $update->appendLog('Running migrations...');
            $this->artisanMigrateCentral($update, ['migrate', '--force']);

            $update->appendLog('Running tenant database migrations...');
            $this->artisanTenant($update, ['tenants:migrate', '--force', '--no-interaction']);

            $update->appendLog('Disabling maintenance mode...');
            $this->artisanCentral($update, ['up']);

            $update->appendLog('Cleaning up temporary files...');
            $this->cleanup();

            $update->appendLog("Update completed. Backup retained at: {$backupPath}");
        } catch (Throwable $e) {
            // leave app down; job will call rollback()
            throw $e;
        }
    }

    public function rollback(SystemUpdate $update): void
    {
        $latestBackup = $this->findLatestBackup();
        if (!$latestBackup) {
            $update->appendLog('Rollback skipped: no backup available (nothing to restore).');
        } else {
            $update->appendLog("Restoring backup from: {$latestBackup}");
            $this->restoreBackup($update, $latestBackup);

            $update->appendLog('Re-installing composer dependencies after restore...');
            $this->runProcess($update, ['composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'], $this->basePath, 3600);

            $this->clearApplicationCaches($update);
        }

        $update->appendLog('Bringing app back up...');
        try {
            $this->artisanCentral($update, ['up']);
        } catch (Throwable $e) {
            $update->appendLog('Failed to disable maintenance mode: ' . $e->getMessage());
        }
    }

    private function ensureDirs(): void
    {
        File::ensureDirectoryExists($this->workDir);
        File::ensureDirectoryExists($this->backupDir);
    }

    private function backupCurrentSystem(SystemUpdate $update): string
    {
        $stamp = now()->format('Ymd_His') . '_' . Str::random(6);
        $backupPath = $this->backupDir . DIRECTORY_SEPARATOR . "backup_{$stamp}";
        File::ensureDirectoryExists($backupPath);

        $update->appendLog('Backing up current system...');

        $exclude = ['.env', 'storage', 'vendor', 'node_modules', '.git'];

        foreach (File::directories($this->basePath) as $dir) {
            $name = basename($dir);
            if (in_array($name, $exclude, true)) {
                continue;
            }
            File::copyDirectory($dir, $backupPath . DIRECTORY_SEPARATOR . $name);
        }

        foreach (File::files($this->basePath) as $file) {
            if (in_array($file->getFilename(), $exclude, true)) {
                continue;
            }
            File::copy($file->getPathname(), $backupPath . DIRECTORY_SEPARATOR . $file->getFilename());
        }

        return $backupPath;
    }

    private function downloadLatestReleaseZip(SystemUpdate $update): array
    {
        $owner = (string) config('system_update.github.owner');
        $repo = (string) config('system_update.github.repo');
        $token = config('system_update.github.token');
        $assetName = (string) config('system_update.github.asset_name');

        if ($owner === '' || $repo === '') {
            throw new \RuntimeException('Missing UPDATE_GITHUB_OWNER / UPDATE_GITHUB_REPO.');
        }

        $update->appendLog("Fetching latest GitHub release for {$owner}/{$repo}...");

        $tlsOptions = $this->githubTlsOptions();
        $this->appendGithubTlsWarningIfNeeded($update, $tlsOptions);

        $apiTimeout = (int) config('system_update.github.http_timeout_api', 120);

        $req = Http::withOptions($tlsOptions)
            ->timeout($apiTimeout)
            ->connectTimeout(min(30, $apiTimeout))
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'Barangay-SystemUpdater']);
        if ($token) {
            $req = $req->withToken($token);
        }

        $release = $req
            ->get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest")
            ->throw()
            ->json();

        $tag = $release['tag_name'] ?? 'unknown';
        $assets = $release['assets'] ?? [];

        $asset = collect($assets)->firstWhere('name', $assetName);
        $url = $asset['browser_download_url'] ?? null;
        if (!$url) {
            throw new \RuntimeException("Release asset not found: {$assetName}. Upload it to the GitHub Release.");
        }

        $zipPath = $this->workDir . DIRECTORY_SEPARATOR . 'release.zip';

        $downloadTimeout = (int) config('system_update.github.http_timeout_download', 1800);

        $update->appendLog("Downloading asset {$assetName} ({$tag})...");
        $binReq = Http::withOptions($tlsOptions)
            ->timeout($downloadTimeout)
            ->connectTimeout(min(60, $downloadTimeout))
            ->withHeaders(['User-Agent' => 'Barangay-SystemUpdater']);
        if ($token) {
            $binReq = $binReq->withToken($token);
        }
        $binReq->sink($zipPath)->get($url)->throw();

        return [$tag, $zipPath];
    }

    /**
     * GitHub calls use TLS; Windows PHP often lacks a CA bundle (cURL error 60).
     *
     * @return array<string, mixed>
     */
    private function githubTlsOptions(): array
    {
        $caPath = (string) config('system_update.github.curl_ca_bundle', '');
        if ($caPath !== '' && is_readable($caPath)) {
            return ['verify' => $caPath];
        }

        if (!filter_var(config('system_update.github.verify_ssl'), FILTER_VALIDATE_BOOLEAN)) {
            return ['verify' => false];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $tlsOptions
     */
    private function appendGithubTlsWarningIfNeeded(SystemUpdate $update, array $tlsOptions): void
    {
        if (($tlsOptions['verify'] ?? true) === false) {
            $update->appendLog('WARNING: GitHub TLS verify is disabled (UPDATE_GITHUB_VERIFY_SSL=false). Use only in local dev; prefer UPDATE_GITHUB_CURL_CAINFO or php.ini curl.cainfo in production.');
        }
    }

    private function extractZip(SystemUpdate $update, string $zipPath): string
    {
        $extractPath = $this->workDir . DIRECTORY_SEPARATOR . 'extracted';
        File::deleteDirectory($extractPath);
        File::ensureDirectoryExists($extractPath);

        $update->appendLog('Extracting ZIP...');

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);
        if ($opened !== true) {
            throw new \RuntimeException('Failed to open ZIP archive.');
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new \RuntimeException('Failed to extract ZIP archive.');
        }
        $zip->close();

        return $this->resolveReleaseRoot($extractPath);
    }

    private function resolveReleaseRoot(string $extractPath): string
    {
        // If the zip contains a single top-level directory, use it as root.
        $entries = collect(File::directories($extractPath))->values();
        if ($entries->count() === 1 && File::isDirectory($entries->first())) {
            return $entries->first();
        }
        return $extractPath;
    }

    private function overwriteApplication(SystemUpdate $update, string $releaseRoot): void
    {
        $exclude = ['.env', 'storage', 'vendor', 'node_modules', '.git'];

        foreach (File::directories($releaseRoot) as $dir) {
            $name = basename($dir);
            if (in_array($name, $exclude, true)) {
                continue;
            }

            $target = $this->basePath . DIRECTORY_SEPARATOR . $name;
            File::deleteDirectory($target);
            File::copyDirectory($dir, $target);
        }

        foreach (File::files($releaseRoot) as $file) {
            if (in_array($file->getFilename(), $exclude, true)) {
                continue;
            }
            File::copy($file->getPathname(), $this->basePath . DIRECTORY_SEPARATOR . $file->getFilename());
        }

        // Ensure storage is linked (safe to re-run).
        try {
            $this->artisanCentral($update, ['storage:link']);
        } catch (Throwable $e) {
            $update->appendLog('storage:link failed (non-fatal): ' . $e->getMessage());
        }
    }

    private function restoreBackup(SystemUpdate $update, string $backupPath): void
    {
        $exclude = ['.env', 'storage', 'vendor', 'node_modules', '.git'];

        foreach (File::directories($backupPath) as $dir) {
            $name = basename($dir);
            if (in_array($name, $exclude, true)) {
                continue;
            }

            $target = $this->basePath . DIRECTORY_SEPARATOR . $name;
            File::deleteDirectory($target);
            File::copyDirectory($dir, $target);
        }

        foreach (File::files($backupPath) as $file) {
            if (in_array($file->getFilename(), $exclude, true)) {
                continue;
            }
            File::copy($file->getPathname(), $this->basePath . DIRECTORY_SEPARATOR . $file->getFilename());
        }
    }

    private function findLatestBackup(): ?string
    {
        if (!File::exists($this->backupDir)) {
            return null;
        }

        return collect(File::directories($this->backupDir))
            ->sortDesc()
            ->values()
            ->first();
    }

    private function cleanup(): void
    {
        File::deleteDirectory($this->workDir . DIRECTORY_SEPARATOR . 'extracted');
        File::delete($this->workDir . DIRECTORY_SEPARATOR . 'release.zip');
    }

    private function clearApplicationCaches(SystemUpdate $update): void
    {
        $update->appendLog('Clearing Laravel caches (file-based)...');

        // File-based purge first. This never loads the app config and therefore
        // can't be taken down by a broken DB connection, stale service provider,
        // or a fresh release.zip that doesn't yet know about the `central` alias.
        $bootstrapCache = $this->basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache';
        $targets = [
            $bootstrapCache . DIRECTORY_SEPARATOR . 'config.php',
            $bootstrapCache . DIRECTORY_SEPARATOR . 'services.php',
            $bootstrapCache . DIRECTORY_SEPARATOR . 'packages.php',
            $bootstrapCache . DIRECTORY_SEPARATOR . 'events.php',
            $bootstrapCache . DIRECTORY_SEPARATOR . 'routes-v7.php',
            $bootstrapCache . DIRECTORY_SEPARATOR . 'routes.php',
        ];
        foreach ($targets as $file) {
            if (File::exists($file)) {
                try {
                    File::delete($file);
                } catch (Throwable $e) {
                    $update->appendLog('Could not delete ' . $file . ': ' . $e->getMessage());
                }
            }
        }

        foreach ([
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
            $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data',
        ] as $dir) {
            if (File::isDirectory($dir)) {
                try {
                    foreach (File::allFiles($dir) as $file) {
                        if ($file->getFilename() === '.gitignore') {
                            continue;
                        }
                        @unlink($file->getPathname());
                    }
                } catch (Throwable $e) {
                    $update->appendLog('Could not clean ' . $dir . ': ' . $e->getMessage());
                }
            }
        }

        // Best-effort artisan call. If the freshly-overwritten app can't boot
        // (e.g. stale release.zip missing the central alias), don't abort the
        // whole update — the file-based purge above is sufficient to let the
        // next step (migrations) pick up fresh config.
        try {
            $this->artisanCentral($update, ['optimize:clear']);
        } catch (Throwable $e) {
            $update->appendLog('WARNING: artisan optimize:clear failed; continuing with file-based cache purge only.');
            $update->appendLog('Reason: ' . trim($e->getMessage()));
        }
    }

    private function artisanCentral(SystemUpdate $update, array $args): void
    {
        $cmd = array_merge([PHP_BINARY, 'artisan'], $args);
        $this->runProcess($update, $cmd, $this->basePath, 3600, $this->centralSubprocessEnv());
    }

    private function artisanMigrateCentral(SystemUpdate $update, array $args): void
    {
        $cmd = array_merge(
            [PHP_BINARY, 'artisan'],
            $args,
            ['--database=' . $this->centralConnection],
        );
        $this->runProcess($update, $cmd, $this->basePath, 3600, $this->centralSubprocessEnv());
    }

    private function artisanTenant(SystemUpdate $update, array $args): void
    {
        $cmd = array_merge([PHP_BINARY, 'artisan'], $args);
        $this->runProcess($update, $cmd, $this->basePath, 3600, $this->centralSubprocessEnv());
    }

    /**
     * Force artisan subprocesses that rely on the default DB connection to use central.
     *
     * Note: many artisan commands (e.g. `down`, `up`) do NOT support `--database`, so we
     * cannot rely on that flag globally.
     */
    private function centralSubprocessEnv(): array
    {
        return [
            'DB_CONNECTION' => $this->centralConnection,
            'APP_UPDATE_BASE_DB_CONNECTION' => $this->baseConnection,
        ];
    }

    private function runProcess(SystemUpdate $update, array $cmd, string $cwd, int $timeoutSeconds, ?array $env = null): void
    {
        $update->appendLog('$ ' . implode(' ', $cmd));

        $process = new Process($cmd, $cwd, $env, null, $timeoutSeconds);
        $process->run(function ($type, $buffer) use ($update) {
            $buffer = trim((string) $buffer);
            if ($buffer !== '') {
                $update->appendLog($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            $err = trim($process->getErrorOutput());
            $out = trim($process->getOutput());
            throw new \RuntimeException($err !== '' ? $err : ($out !== '' ? $out : 'Command failed.'));
        }
    }
}

