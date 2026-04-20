<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemUpdate extends Model
{
    /**
     * System update logs are stored on the central database connection.
     */
    protected $connection = 'central';

    protected $fillable = [
        'version',
        'status',
        'log',
        'maintenance_bypass_secret',
    ];

    public const STATUS_PENDING = 'pending';
    /** Dispatched to the queue; worker has not started the job yet. */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    public function appendLog(string $line): void
    {
        $prefix = now()->toDateTimeString();
        $this->log = rtrim(($this->log ?? '')) . "\n[{$prefix}] {$line}";
        $this->save();
    }

    public function maintenanceBypassUrl(): ?string
    {
        if (!$this->maintenance_bypass_secret) {
            return null;
        }

        return rtrim((string) config('app.url'), '/') . '/' . $this->maintenance_bypass_secret;
    }
}

