<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mediation extends Model
{
    use BelongsToTenant;

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'mediator_user_id',
        'scheduled_at',
        'status',
        'agreement_notes',
        'settlement_terms',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // tenant() relationship is provided by BelongsToTenant trait

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function mediator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mediator_user_id');
    }
}
