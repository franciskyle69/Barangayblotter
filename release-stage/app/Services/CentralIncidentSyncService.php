<?php

namespace App\Services;

use App\Models\CentralIncidentSummary;
use App\Models\Incident;

class CentralIncidentSyncService
{
    public function sync(Incident $incident): void
    {
        $tenant = $incident->tenant;

        if (!$tenant) {
            return;
        }

        $summary = CentralIncidentSummary::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'tenant_incident_id' => $incident->id,
        ]);

        $summary->fill([
            'tenant_name' => $tenant->name,
            'tenant_slug' => $tenant->slug,
            'blotter_number' => $incident->blotter_number,
            'incident_type' => $incident->incident_type,
            'status' => $incident->status,
            'incident_date' => $incident->incident_date,
            'reported_by_user_id' => $incident->reported_by_user_id,
            'reported_by_name' => $incident->reportedBy?->name,
            'created_at_in_tenant' => $incident->created_at,
            'updated_at_in_tenant' => $incident->updated_at,
        ]);

        $summary->save();
    }

    public function delete(Incident $incident): void
    {
        CentralIncidentSummary::query()
            ->where('tenant_id', $incident->tenant_id)
            ->where('tenant_incident_id', $incident->id)
            ->delete();
    }
}
