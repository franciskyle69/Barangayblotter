<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CentralActivityLog extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_email',
        'action',
        'description',
        'target_type',
        'target_id',
        'tenant_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
