<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use HasFactory;

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
        'avatar',
        'salary_name',
        'salary_amount',
        'salary_type',
        'password',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'hire_date' => 'date',
        'password' => 'hashed',
        'salary_amount' => 'decimal:2',
        'salary_type' => 'string',
        'email_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];



    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function hasPermission(string $key): bool
    {
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if ($permission->key === $key) {
                    return true;
                }
            }
        }

        return false;
    }
}
