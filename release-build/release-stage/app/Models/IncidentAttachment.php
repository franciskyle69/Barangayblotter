<?php

namespace App\Models;

use App\Models\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class IncidentAttachment extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'incident_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
