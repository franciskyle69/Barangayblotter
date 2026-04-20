<?php

namespace App\Jobs;

use App\Models\SystemUpdate;
use App\Services\SystemUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunSystemUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(public int $systemUpdateId) {}

    public function handle(SystemUpdateService $service): void
    {
        $update = SystemUpdate::findOrFail($this->systemUpdateId);

        $update->update(['status' => SystemUpdate::STATUS_RUNNING]);
        $update->appendLog('Update started.');
        $update->appendLog(
            'SystemUpdateService loaded from: '
            . (new \ReflectionClass(SystemUpdateService::class))->getFileName()
        );

        try {
            $service->run($update);
            $update->update(['status' => SystemUpdate::STATUS_SUCCESS]);
            $update->appendLog('Update finished successfully.');
        } catch (Throwable $e) {
            $update->appendLog('ERROR: ' . $e->getMessage());
            $update->appendLog('Attempting rollback...');

            try {
                $service->rollback($update);
                $update->appendLog('Rollback completed.');
            } catch (Throwable $rollbackError) {
                $update->appendLog('ROLLBACK ERROR: ' . $rollbackError->getMessage());
            }

            $update->update(['status' => SystemUpdate::STATUS_FAILED]);
            throw $e;
        }
    }
}

