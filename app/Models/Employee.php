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
        'user_id',
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
        'salary_name',
        'salary_amount',
        'salary_type',
        'password',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'hourly_rate' => 'decimal:2',
        'hire_date' => 'date',
        'password' => 'hashed',
        'salary_amount' => 'decimal:2',
        'wallet' => 'decimal:2',
        'salary_type' => 'string',
        'email_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function canonicalUser(): ?User
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user;
        }

        if ($this->user_id) {
            return $this->user()->first();
        }

        $email = trim((string) $this->email);
        if ($email !== '') {
            $users = User::where('email', $email)->limit(2)->get(['id']);

            return $users->count() === 1 ? User::find($users->first()->id) : null;
        }

        return null;
    }

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
