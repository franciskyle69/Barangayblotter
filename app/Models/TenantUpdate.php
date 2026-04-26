<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUpdate extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'triggered_by_user_id',
        'status',
        'log',
        'started_at',
        'finished_at',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function appendLog(string $line): void
    {
        $prefix = now()->toDateTimeString();
        $this->log = rtrim(($this->log ?? '')) . "\n[{$prefix}] {$line}";
        $this->save();
    }
}

