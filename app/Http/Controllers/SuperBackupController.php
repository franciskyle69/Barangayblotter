<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SuperBackupController extends Controller
{
    /**
     * Hard cap on uploaded restore payloads (in KB). Defense-in-depth
     * alongside the `max:` validator — catches misconfigured php.ini
     * upload_max_filesize settings where a gigantic file sneaks past.
     */
    private const MAX_RESTORE_UPLOAD_KB = 102400; // 100 MB

    public function index(DatabaseBackupService $backupService): Response
    {
        return Inertia::render('Super/BackupRestore', [
            'backups' => $backupService->listBackups(),
            'backupDirectory' => $backupService->absoluteBackupDirectory(),
            'tenants' => \App\Models\Tenant::query()->orderBy('name')->get(['id', 'name'])->toArray(),
        ]);
    }

    public function create(DatabaseBackupService $backupService): RedirectResponse
    {
        try {
            $onlyTenantId = request()->integer('tenant_id');
            $backup = $backupService->createBackup($onlyTenantId > 0 ? $onlyTenantId : null);

            ActivityLogService::record(
                request: request(),
                action: 'super.backup.create',
                description: "Created backup file {$backup['filename']}.",
                metadata: [
                    'filename' => $backup['filename'],
                    'only_tenant_id' => $onlyTenantId > 0 ? $onlyTenantId : null,
                ],
                targetType: 'backup_file',
                targetId: $backup['filename'],
            );

            return back()->with('success', "Backup created successfully: {$backup['filename']}");
        } catch (\Throwable $e) {
            ActivityLogService::record(
                request: request(),
                action: 'super.backup.create_failed',
                description: 'Failed to create backup file.',
                metadata: ['error' => $e->getMessage()],
                targetType: 'backup_file',
            );

            report($e);

            return back()->with('error', $this->userFacingError('Failed to create backup', $e));
        }
    }

    public function download(Request $request, string $filename, DatabaseBackupService $backupService)
    {
        // Defense in depth even though the route regex already restricts
        // characters: reject any filename that slipped through with path
        // traversal characters.
        $this->assertSafeFilename($filename);

        try {
            $relativePath = $backupService->relativePathFor($filename);

            $existsOnLocalDisk = Storage::disk('local')->exists($relativePath);

            if (!$existsOnLocalDisk) {
                $legacyPath = storage_path('app/' . $relativePath);
                if (!is_file($legacyPath)) {
                    abort(404);
                }

                ActivityLogService::record(
                    request: $request,
                    action: 'super.backup.download',
                    description: "Downloaded backup file {$filename}.",
                    metadata: ['filename' => $filename, 'source' => 'legacy_path'],
                    targetType: 'backup_file',
                    targetId: $filename,
                );

                return response()->download($legacyPath, $filename);
            }

            ActivityLogService::record(
                request: $request,
                action: 'super.backup.download',
                description: "Downloaded backup file {$filename}.",
                metadata: ['filename' => $filename, 'source' => 'local_disk'],
                targetType: 'backup_file',
                targetId: $filename,
            );

            return response()->download(Storage::disk('local')->path($relativePath), $filename);
        } catch (RuntimeException) {
            abort(404);
        }
    }

    public function restoreFromStored(Request $request, string $filename, DatabaseBackupService $backupService): RedirectResponse
    {
        $this->assertSafeFilename($filename);

        $request->validate([
            'confirmation' => ['required', 'in:RESTORE'],
        ]);

        try {
            $backupService->restoreFromStoredFile($filename);

            ActivityLogService::record(
                request: $request,
                action: 'super.backup.restore_stored',
                description: "Restored data from backup file {$filename}.",
                metadata: ['filename' => $filename],
                targetType: 'backup_file',
                targetId: $filename,
            );

            return back()->with('success', "Backup restored successfully from {$filename}.");
        } catch (\Throwable $e) {
            ActivityLogService::record(
                request: $request,
                action: 'super.backup.restore_stored_failed',
                description: "Failed to restore data from backup file {$filename}.",
                metadata: [
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ],
                targetType: 'backup_file',
                targetId: $filename,
            );

            report($e);

            return back()->with('error', $this->userFacingError('Failed to restore backup', $e));
        }
    }

    public function restoreFromUpload(Request $request, DatabaseBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESTORE'],
            'backup_file' => [
                'required',
                'file',
                'max:' . self::MAX_RESTORE_UPLOAD_KB,
                'mimetypes:application/json,text/plain',
            ],
        ]);

        try {
            $backupFile = $request->file('backup_file');

            // Filename + extension check. `mimetypes` is already validated
            // above, but we also want the file *name* to match our
            // expectation so we reject obvious shenanigans like
            // `backup.php;.json`, which some servers happily execute.
            $originalName = (string) $backupFile->getClientOriginalName();
            if (strcasecmp(pathinfo($originalName, PATHINFO_EXTENSION), 'json') !== 0) {
                throw new RuntimeException('Backup files must have a .json extension.');
            }

            // Size recheck — the validator uses KB, but a bug in php.ini
            // (upload_max_filesize larger than post_max_size, e.g.) could
            // let something oversize land anyway.
            if ((int) $backupFile->getSize() > self::MAX_RESTORE_UPLOAD_KB * 1024) {
                throw new RuntimeException('Uploaded backup exceeds the allowed size.');
            }

            $contents = file_get_contents($backupFile->getRealPath());

            if ($contents === false) {
                throw new RuntimeException('Unable to read uploaded backup file.');
            }

            // Shape check: backups are JSON objects. Fail fast on anything
            // that doesn't start with `{` so an attacker can't feed us a
            // 100MB blob of garbage and see how the restore engine reacts.
            $firstNonWhitespace = ltrim($contents);
            if ($firstNonWhitespace === '' || $firstNonWhitespace[0] !== '{') {
                throw new RuntimeException('Uploaded file is not a valid JSON backup.');
            }

            $backupService->restoreFromJsonString($contents);

            ActivityLogService::record(
                request: $request,
                action: 'super.backup.restore_upload',
                description: 'Restored data from uploaded backup file.',
                metadata: [
                    'uploaded_name' => $originalName,
                    'uploaded_size' => $backupFile->getSize(),
                ],
                targetType: 'backup_file',
                targetId: $originalName,
            );

            return back()->with('success', 'Backup restored successfully from uploaded file.');
        } catch (\Throwable $e) {
            ActivityLogService::record(
                request: $request,
                action: 'super.backup.restore_upload_failed',
                description: 'Failed to restore data from uploaded backup file.',
                metadata: ['error' => $e->getMessage()],
                targetType: 'backup_file',
            );

            report($e);

            return back()->with('error', $this->userFacingError('Failed to restore uploaded backup', $e));
        }
    }

    /**
     * Rejects filenames containing path traversal sequences. Belt-and-
     * braces: the route regex `[A-Za-z0-9._-]+` already blocks most of
     * these, but defense-in-depth is cheap and catches any future route
     * change that loosens the regex.
     */
    private function assertSafeFilename(string $filename): void
    {
        if ($filename === '' || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(400, 'Invalid filename.');
        }
    }

    /**
     * Redacts raw exception messages in production to avoid leaking
     * internal paths, SQL errors, or stack shape hints via the flash
     * channel. In non-production environments we keep the detail so
     * developers can actually diagnose failures.
     */
    private function userFacingError(string $prefix, \Throwable $e): string
    {
        if (app()->environment('production')) {
            return $prefix . '. See server logs for details.';
        }

        return $prefix . ': ' . $e->getMessage();
    }
}
