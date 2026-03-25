<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant Model - Bridge between Tenancy Framework and Custom Database Schema
 *
 * This model extends Eloquent and works alongside the Tenancy framework.
 * It maintains compatibility with existing relationships while integrating
 * with Tenancy's multi-tenancy system.
 */
class Tenant extends Model
{
    use HasFactory;
    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'subdomain',
        'custom_domain',
        'barangay',
        'address',
        'contact_phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /* ─── Tenancy Framework Integration ─── */

    /**
     * Get the Tenancy tenant ID.
     * For now, we use the Eloquent model's ID as the Tenancy tenant ID.
     */
    public function getTenancyId(): string
    {
        return (string) $this->id;
    }

    /**
     * Get all domains associated with this tenant.
     * Used by Tenancy framework for domain resolution.
     */
    public function getDomains(): array
    {
        $domains = [];

        if ($this->custom_domain) {
            $domains[] = $this->custom_domain;
        }

        if ($this->subdomain) {
            $baseDomain = config('app.url');
            // Extract base domain from APP_URL
            preg_match('/https?:\/\/(.*?)(?::\d+)?(?:\/|$)/', $baseDomain, $matches);
            $base = $matches[1] ?? 'localhost';
            $domains[] = "{$this->subdomain}.{$base}";
        }

        return $domains;
    }

    /* ─── Domain Resolution ─── */

    /**
     * Resolve a tenant from the HTTP host header.
     * Priority: custom_domain → subdomain → null.
     */
    public static function resolveFromHost(string $host): ?self
    {
        // Strip port if present (e.g localhost:8000)
        $host = strtolower(preg_replace('/:\d+$/', '', $host));

        // 1. Try custom domain match (exact)
        $tenant = static::where('custom_domain', $host)->first();
        if ($tenant) {
            return $tenant;
        }

        // 2. Extract subdomain from base domain
        $baseDomain = strtolower(config('app.url'));
        preg_match('/https?:\/\/(.*?)(?::\d+)?(?:\/|$)/', $baseDomain, $matches);
        $base = strtolower($matches[1] ?? 'localhost');

        if ($base && str_ends_with($host, '.' . $base)) {
            $subdomain = str_replace('.' . $base, '', $host);
            if ($subdomain && $subdomain !== 'www') {
                return static::where('subdomain', $subdomain)->first();
            }
        }

        return null;
    }

    /**
     * Get the full URL for this tenant.
     */
    public function getUrl(): string
    {
        if ($this->custom_domain) {
            return 'https://' . $this->custom_domain;
        }

        if ($this->subdomain) {
            $appUrl = config('app.url');
            preg_match('/https?:\/\/(.*?)(?::\d+)?(?:\/|$)/', $appUrl, $matches);
            $base = $matches[1] ?? 'localhost';
            $scheme = str_starts_with($appUrl, 'https') ? 'https' : 'http';

            // Extract port if present
            preg_match('/:\d+/', $appUrl, $portMatches);
            $port = $portMatches[0] ?? '';

            return $scheme . '://' . $this->subdomain . '.' . $base . $port;
        }

        return config('app.url');
    }

    /* ─── Relationships ─── */

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function patrolLogs(): HasMany
    {
        return $this->hasMany(PatrolLog::class);
    }

    public function mediations(): HasMany
    {
        return $this->hasMany(Mediation::class);
    }

    public function blotterRequests(): HasMany
    {
        return $this->hasMany(BlotterRequest::class);
    }

    /* ─── Plan Helpers ─── */

    public function incidentCountForCurrentMonth(): int
    {
        return $this->incidents()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function canAddIncident(): bool
    {
        $plan = $this->plan;
        if ($plan->hasUnlimitedIncidents()) {
            return true;
        }
        return $this->incidentCountForCurrentMonth() < $plan->incident_limit_per_month;
    }
}
