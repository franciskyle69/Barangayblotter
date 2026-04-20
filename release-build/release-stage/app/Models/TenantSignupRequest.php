<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSignupRequest extends Model
{
    protected $connection = 'central';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_name',
        'slug',
        'subdomain',
        'custom_domain',
        'barangay',
        'address',
        'contact_phone',
        'contact_name',
        'contact_email',
        'requested_admin_name',
        'requested_admin_email',
        'requested_admin_phone',
        'requested_admin_role',
        'requested_admin_password_hash',
        'requested_plan_id',
        'status',
        'review_notes',
        'reviewed_by_user_id',
        'reviewed_at',
        'processed_tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function processedTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'processed_tenant_id');
    }
}
