<?php

namespace App\Models;

use App\Models\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, UsesTenantConnection;

    public const ROLE_BARANGAY_ADMIN = 'barangay_admin';
    public const ROLE_PUROK_SECRETARY = 'purok_secretary';
    public const ROLE_PUROK_LEADER = 'purok_leader';
    public const ROLE_COMMUNITY_WATCH = 'community_watch';
    public const ROLE_MEDIATOR = 'mediator';
    public const ROLE_RESIDENT = 'resident';
    public const ROLE_CITIZEN = 'citizen';

    public static function tenantRoles(): array
    {
        return [
            self::ROLE_BARANGAY_ADMIN => 'Barangay Admin',
            self::ROLE_PUROK_SECRETARY => 'Barangay Secretary',
            self::ROLE_PUROK_LEADER => 'Barangay Captain',
            self::ROLE_COMMUNITY_WATCH => 'Community Watch',
            self::ROLE_MEDIATOR => 'Community Mediator',
            self::ROLE_RESIDENT => 'Resident',
            self::ROLE_CITIZEN => 'Citizen',
        ];
    }

    public static function tenantAdminRoles(): array
    {
        return [self::ROLE_BARANGAY_ADMIN];
    }

    public static function tenantPermissions(): array
    {
        $allPermissions = [];

        foreach (self::tenantPermissionMatrix() as $rolePermissions) {
            $allPermissions = array_merge($allPermissions, $rolePermissions);
        }

        return array_values(array_unique($allPermissions));
    }

    public static function tenantPermissionMatrix(): array
    {
        $adminPermissions = [
            'view_dashboard',
            'view_incidents',
            'create_incidents',
            'request_blotter_copy',
            'manage_branding',
            'manage_users',
            'manage_incidents',
            'manage_mediations',
            'manage_patrol_logs',
            'review_blotter_requests',
            'manage_account_settings',
        ];

        return [
            self::ROLE_BARANGAY_ADMIN => $adminPermissions,
            self::ROLE_PUROK_SECRETARY => [
                'view_dashboard',
                'view_incidents',
                'create_incidents',
                'request_blotter_copy',
                'manage_account_settings',
            ],
            self::ROLE_PUROK_LEADER => [
                'view_dashboard',
                'view_incidents',
                'create_incidents',
                'request_blotter_copy',
                'manage_account_settings',
            ],
            self::ROLE_COMMUNITY_WATCH => [
                'view_dashboard',
                'view_incidents',
                'create_incidents',
                'manage_account_settings',
            ],
            self::ROLE_MEDIATOR => [
                'view_dashboard',
                'view_incidents',
                'manage_account_settings',
            ],
            self::ROLE_RESIDENT => [
                'view_dashboard',
                'view_incidents',
                'create_incidents',
                'request_blotter_copy',
                'manage_account_settings',
            ],
            self::ROLE_CITIZEN => [
                'view_dashboard',
                'view_incidents',
                'create_incidents',
                'request_blotter_copy',
                'manage_account_settings',
            ],
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_super_admin',
        'must_change_password',
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
            'role' => 'string',
            'is_super_admin' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function roleIn(Tenant $tenant): ?string
    {
        return $this->role ?: null;
    }

    public function hasRoleIn(Tenant $tenant, string|array $roles): bool
    {
        $role = $this->roleIn($tenant);
        if (!$role) {
            return false;
        }
        return in_array($role, (array) $roles, true);
    }

    public function hasTenantPermission(Tenant $tenant, string|array $permissions, bool $requireAll = false): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $roleName = $this->roleIn($tenant);
        if (!$roleName) {
            return false;
        }

        $permissionList = $this->normalizeTenantPermissions($permissions);
        if ($permissionList === []) {
            return true;
        }

        $grantedPermissions = $this->grantedTenantPermissionsForRole($tenant, $roleName);

        $grantedLookup = array_fill_keys($grantedPermissions, true);

        if ($requireAll) {
            foreach ($permissionList as $permission) {
                if (!isset($grantedLookup[$permission])) {
                    return false;
                }
            }

            return true;
        }

        foreach ($permissionList as $permission) {
            if (isset($grantedLookup[$permission])) {
                return true;
            }
        }

        return false;
    }

    private function normalizeTenantPermissions(string|array $permissions): array
    {
        return array_values(array_filter(array_map(
            static fn($permission) => is_string($permission) ? trim($permission) : null,
            (array) $permissions,
        )));
    }

    private function grantedTenantPermissionsForRole(Tenant $tenant, string $roleName): array
    {
        return self::tenantPermissionMatrix()[$roleName] ?? [];
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
