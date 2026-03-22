<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'incident_limit_per_month',
        'online_complaint_submission',
        'mediation_scheduling',
        'sms_status_updates',
        'analytics_dashboard',
        'auto_case_number',
        'qr_verification',
        'central_monitoring',
        'price_monthly',
    ];

    protected function casts(): array
    {
        return [
            'online_complaint_submission' => 'boolean',
            'mediation_scheduling' => 'boolean',
            'sms_status_updates' => 'boolean',
            'analytics_dashboard' => 'boolean',
            'auto_case_number' => 'boolean',
            'qr_verification' => 'boolean',
            'central_monitoring' => 'boolean',
            'price_monthly' => 'decimal:2',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function hasUnlimitedIncidents(): bool
    {
        return $this->incident_limit_per_month === null;
    }
}
