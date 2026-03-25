<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlotterRequest extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PRINTED = 'printed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'incident_id',
        'requested_by_user_id',
        'purpose',
        'status',
        'admin_user_id',
        'remarks',
        'certificate_path',
        'verification_code',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'printed_at' => 'datetime',
        ];
    }

    // tenant() relationship is provided by BelongsToTenant trait

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
