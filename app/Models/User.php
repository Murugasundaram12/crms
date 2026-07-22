<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected ?EloquentCollection $resolvedAssignedRoles = null;

    protected ?array $resolvedPermissionKeys = null;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'designation',
        'role',
        'address',
        'hourly_rate',
        'hire_date',
        'status',
        'wallet',
        'avatar',
        'password',
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
            'hire_date' => 'date',
            'hourly_rate' => 'decimal:2',
            'wallet' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    /**
     * A user may have many roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function directPermissions(): MorphToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions', 'model_id', 'permission_id');
    }

    public function mobileApiTokens(): HasMany
    {
        return $this->hasMany(MobileApiToken::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(EmployeeLocation::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(EmployeeDevice::class, 'employee_id');
    }

    public function locationTrackings(): HasMany
    {
        return $this->hasMany(LocationTracking::class, 'employee_id');
    }

    public function assignedRoles(): EloquentCollection
    {
        if ($this->resolvedAssignedRoles instanceof EloquentCollection) {
            return $this->resolvedAssignedRoles;
        }

        $this->loadMissing('roles.permissions');

        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->with('permissions')->get();

        if ($roles->isNotEmpty()) {
            return $this->resolvedAssignedRoles = $roles;
        }

        if (blank($this->role)) {
            return $this->resolvedAssignedRoles = new EloquentCollection();
        }

        return $this->resolvedAssignedRoles = Role::query()
            ->with('permissions')
            ->where('name', $this->role)
            ->get();
    }

    public function hasPermission(string $key): bool
    {
        if (($this->role ?? null) === 'Super Admin' || $this->assignedRoles()->contains('name', 'Super Admin')) {
            return true;
        }

        return in_array($key, $this->effectivePermissionKeys(), true);
    }

    public function effectivePermissionKeys(): array
    {
        if (is_array($this->resolvedPermissionKeys)) {
            return $this->resolvedPermissionKeys;
        }

        $permissionKeys = [];

        foreach ($this->assignedRoles() as $role) {
            foreach ($role->permissions as $permission) {
                if (! empty($permission->key)) {
                    $permissionKeys[] = $permission->key;
                }
            }
        }

        foreach ($this->directPermissionKeys() as $permissionKey) {
            $permissionKeys[] = $permissionKey;
        }

        $this->resolvedPermissionKeys = array_values(array_unique($permissionKeys));

        return $this->resolvedPermissionKeys;
    }

    public function clearResolvedPermissions(): void
    {
        $this->resolvedPermissionKeys = null;
        $this->resolvedAssignedRoles = null;
        unset($this->relations['directPermissions'], $this->relations['roles']);
    }

    private function directPermissionKeys(): array
    {
        if (! Schema::hasTable('model_has_permissions') || ! Schema::hasTable('permissions')) {
            return [];
        }

        $permissions = $this->relationLoaded('directPermissions')
            ? $this->directPermissions
            : $this->directPermissions()->get();

        return $permissions
            ->pluck('key')
            ->filter()
            ->values()
            ->all();
    }
}
