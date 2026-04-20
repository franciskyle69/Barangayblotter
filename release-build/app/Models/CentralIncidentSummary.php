<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralIncidentSummary extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'tenant_id',
        'tenant_name',
        'tenant_slug',
        'tenant_incident_id',
        'blotter_number',
        'incident_type',
        'status',
        'incident_date',
        'reported_by_user_id',
        'reported_by_name',
        'created_at_in_tenant',
        'updated_at_in_tenant',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'datetime',
            'created_at_in_tenant' => 'datetime',
            'updated_at_in_tenant' => 'datetime',
        ];
    }
}
