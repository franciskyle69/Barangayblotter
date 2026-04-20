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
    public function index(DatabaseBackupService $backupService): Response
    {
        return Inertia::render('Super/BackupRestore', [
            'backups' => $backupService->listBackups(),
            'backupDirectory' => $backupService->absoluteBackupDirectory(),
        ]);
    }

    public function create(DatabaseBackupService $backupService): RedirectResponse
    {
        try {
            $backup = $backupService->createBackup();

            ActivityLogService::record(
                request: request(),
                action: 'super.backup.create',
                description: "Created backup file {$backup['filename']}.",
                metadata: [
                    'filename' => $backup['filename'],
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

            return back()->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    public function download(Request $request, string $filename, DatabaseBackupService $backupService)
    {
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

            return back()->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }

    public function restoreFromUpload(Request $request, DatabaseBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:RESTORE'],
            'backup_file' => ['required', 'file', 'max:102400', 'mimetypes:application/json,text/plain'],
        ]);

        try {
            $backupFile = $request->file('backup_file');
            $contents = file_get_contents($backupFile->getRealPath());

            if ($contents === false) {
                throw new RuntimeException('Unable to read uploaded backup file.');
            }

            $backupService->restoreFromJsonString($contents);

            ActivityLogService::record(
                request: $request,
                action: 'super.backup.restore_upload',
                description: 'Restored data from uploaded backup file.',
                metadata: [
                    'uploaded_name' => $backupFile->getClientOriginalName(),
                    'uploaded_size' => $backupFile->getSize(),
                ],
                targetType: 'backup_file',
                targetId: $backupFile->getClientOriginalName(),
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

            return back()->with('error', 'Failed to restore uploaded backup: ' . $e->getMessage());
        }
    }
}
