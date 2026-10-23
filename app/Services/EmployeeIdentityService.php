<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmployeeIdentityService
{
    /**
     * Resolve a canonical User model from a User or Employee reference.
     *
     * Bare numeric values are intentionally not resolved because they do not
     * identify whether the caller supplied an employee ID or a user ID.
     */
    public function resolveUser(mixed $identity): ?User
    {
        if ($identity instanceof User) {
            return $identity;
        }

        if ($identity instanceof Employee) {
            if ($identity->user_id) {
                $user = User::find($identity->user_id);
                if ($user) {
                    return $user;
                }
            }

            $email = trim((string) $identity->email);
            if ($email !== '') {
                $users = User::where('email', $email)->limit(2)->get(['id']);

                return $users->count() === 1 ? User::find($users->first()->id) : null;
            }

            return null;
        }

        return null;
    }

    /**
     * Resolve a user when the caller explicitly supplies a user ID.
     */
    public function resolveUserById(?int $userId): ?User
    {
        return $userId && $userId > 0 ? User::find($userId) : null;
    }

    /**
     * Resolve an Employee profile from a User or Employee reference.
     */
    public function resolveEmployee(mixed $identity): ?Employee
    {
        if ($identity instanceof Employee) {
            return $identity;
        }

        if (! Schema::hasTable('employees')) {
            return null;
        }

        if ($identity instanceof User) {
            $mappedEmployees = Employee::where('user_id', $identity->id)->limit(2)->get();
            if ($mappedEmployees->count() === 1) {
                return $mappedEmployees->first();
            }

            if ($mappedEmployees->count() > 1) {
                return null;
            }

            $email = trim((string) $identity->email);
            if ($email !== '') {
                $employees = Employee::where('email', $email)->limit(2)->get(['id']);

                return $employees->count() === 1 ? Employee::find($employees->first()->id) : null;
            }

            return null;
        }

        return null;
    }

    /**
     * Resolve an employee when the caller explicitly supplies an employee ID.
     */
    public function resolveEmployeeById(?int $employeeId): ?Employee
    {
        if (! $employeeId || $employeeId < 1 || ! Schema::hasTable('employees')) {
            return null;
        }

        return Employee::find($employeeId);
    }

    /**
     * Safely synchronize or create an Employee profile record for a canonical User.
     * Preserves legacy employee rows without altering unmapped records.
     */
    public function syncEmployeeRecordForUser(User $user): ?Employee
    {
        if (! Schema::hasTable('employees')) {
            return null;
        }

        $mappedEmployees = Employee::query()
            ->where('user_id', $user->id)
            ->limit(2)
            ->get();

        if ($mappedEmployees->count() > 1) {
            return null;
        }

        $employee = $mappedEmployees->first();

        $email = trim((string) $user->email);
        if (! $employee && $email !== '') {
            $legacyEmployees = Employee::query()
                ->where('email', $email)
                ->limit(2)
                ->get();

            if ($legacyEmployees->count() > 1) {
                return null;
            }

            $employee = $legacyEmployees->first();
        }

        if (! $employee) {
            $employee = new Employee();
        }

        $employee->user_id = $user->id;
        $employee->name = $user->name;
        $employee->email = $user->email;
        $employee->phone = $user->phone;
        $employee->designation = $user->designation;
        $employee->role = $user->role ?: 'Site Engineer';
        $employee->address = $user->address;
        $employee->hourly_rate = $user->hourly_rate ?? 0;
        $employee->hire_date = $user->hire_date;
        $employee->status = $user->status ?: 'active';
        $employee->avatar = $user->avatar;
        $employee->salary_name = $user->salary_name;
        $employee->salary_amount = $user->salary_amount;
        $employee->salary_type = $user->salary_type;

        if (filled($user->password) && blank($employee->password)) {
            $employee->password = $user->password;
        }

        $employee->save();

        return $employee;
    }

    /**
     * Generate an audit data array of legacy employees and their canonical User mapping status.
     */
    public function getMappingReport(): array
    {
        if (! Schema::hasTable('employees')) {
            return [];
        }

        $employees = Employee::query()->orderBy('id')->get();
        $report = [];

        foreach ($employees as $employee) {
            $user = null;

            $user = $this->resolveUser($employee);

            $report[] = [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'user_id' => $user?->id ?? $employee->user_id,
                'user_name' => $user?->name,
                'status' => $user ? 'VERIFIED' : 'UNMAPPED - PRESERVED',
            ];
        }

        return $report;
    }
}
