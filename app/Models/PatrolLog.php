<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'patrol_date',
        'start_time',
        'end_time',
        'area_patrolled',
        'activities',
        'incidents_observed',
        'response_details',
        'response_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'patrol_date' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
