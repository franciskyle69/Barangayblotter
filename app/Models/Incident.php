<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_UNDER_MEDIATION = 'under_mediation';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_ESCALATED = 'escalated_to_barangay';

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_UNDER_MEDIATION => 'Under Mediation',
            self::STATUS_SETTLED => 'Settled',
            self::STATUS_ESCALATED => 'Escalated to Barangay',
        ];
    }

    protected $fillable = [
        'tenant_id',
        'blotter_number',
        'incident_type',
        'description',
        'location',
        'incident_date',
        'complainant_name',
        'complainant_contact',
        'complainant_address',
        'complainant_user_id',
        'respondent_name',
        'respondent_contact',
        'respondent_address',
        'status',
        'reported_by_user_id',
        'submitted_online',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'datetime',
            'submitted_online' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Incident $incident) {
            if (empty($incident->blotter_number)) {
                $tenant = $incident->tenant;
                if ($tenant && $tenant->plan->auto_case_number) {
                    $prefix = strtoupper(substr($tenant->slug, 0, 3));
                    $year = now()->format('Y');
                    $seq = $tenant->incidents()->whereYear('created_at', now()->year)->count() + 1;
                    $incident->blotter_number = sprintf('%s-%s-%04d', $prefix, $year, $seq);
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function complainantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'complainant_user_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }

    public function mediations(): HasMany
    {
        return $this->hasMany(Mediation::class);
    }

    public function blotterRequests(): HasMany
    {
        return $this->hasMany(BlotterRequest::class);
    }
}
