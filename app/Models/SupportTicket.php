<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A support ticket filed by a tenant user against the central team.
 *
 * Lives on the `central` connection so the super-admin can see tickets
 * across all barangays in a single query, and so the table is reachable
 * even when a particular tenant database is unavailable.
 */
class SupportTicket extends Model
{
    protected $connection = 'central';

    public const STATUS_OPEN             = 'open';
    public const STATUS_IN_PROGRESS      = 'in_progress';
    public const STATUS_AWAITING_TENANT  = 'awaiting_tenant';
    public const STATUS_RESOLVED         = 'resolved';
    public const STATUS_CLOSED           = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_AWAITING_TENANT,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public const CATEGORIES = [
        'bug',
        'feature_request',
        'billing',
        'complaint',
        'question',
        'other',
    ];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'tenant_id',
        'subject',
        'category',
        'priority',
        'status',
        'opened_by_user_id',
        'opened_by_name',
        'opened_by_email',
        'closed_at',
        'closed_by_user_id',
        'closure_note',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at'        => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->oldest();
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }
}
