<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_PUROK_SECRETARY = 'purok_secretary';
    public const ROLE_PUROK_LEADER = 'purok_leader';
    public const ROLE_COMMUNITY_WATCH = 'community_watch';
    public const ROLE_MEDIATOR = 'mediator';
    public const ROLE_RESIDENT = 'resident';

    public static function tenantRoles(): array
    {
        return [
            self::ROLE_PUROK_SECRETARY => 'Barangay Secretary',
            self::ROLE_PUROK_LEADER => 'Barangay Captain',
            self::ROLE_COMMUNITY_WATCH => 'Community Watch',
            self::ROLE_MEDIATOR => 'Community Mediator',
            self::ROLE_RESIDENT => 'Resident',
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roleIn(Tenant $tenant): ?string
    {
        $pivot = $this->tenants()->where('tenants.id', $tenant->id)->first()?->pivot;
        return $pivot?->role;
    }

    public function hasRoleIn(Tenant $tenant, string|array $roles): bool
    {
        $role = $this->roleIn($tenant);
        if (!$role) {
            return false;
        }
        return in_array($role, (array) $roles, true);
    }

    public function incidentsReported(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by_user_id');
    }

    public function mediationsAsMediator(): HasMany
    {
        return $this->hasMany(Mediation::class, 'mediator_user_id');
    }

    public function patrolLogs(): HasMany
    {
        return $this->hasMany(PatrolLog::class);
    }

    public function blotterRequests(): HasMany
    {
        return $this->hasMany(BlotterRequest::class, 'requested_by_user_id');
    }
}
