<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolLog extends Model
{
    use BelongsToTenant;

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

    // tenant() relationship is provided by BelongsToTenant trait

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
