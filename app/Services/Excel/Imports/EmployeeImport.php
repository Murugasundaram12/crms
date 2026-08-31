<?php

namespace App\Services\Excel\Imports;

use App\Models\Role;
use App\Models\User;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeImport implements ExcelImportDefinition
{
    public function module(): string { return 'employees'; }
    public function requiredHeaders(): array { return ['name', 'email', 'role', 'address', 'hiredate', 'status', 'password']; }

    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'email'=>$row['email'] ?? null, 'role'=>$row['role'] ?? null,
            'address'=>$row['address'] ?? null, 'hire_date'=>$row['hiredate'] ?? null, 'status'=>$row['status'] ?? null,
            'phone'=>$row['phone'] ?? null, 'designation'=>$row['designation'] ?? null, 'hourly_rate'=>$row['hourlyrate'] ?? 0,
            'salary_name'=>$row['salaryname'] ?? null, 'salary_amount'=>$row['salaryamount'] ?? null, 'salary_type'=>$row['salarytype'] ?? null];
        $validator = Validator::make($data + ['password'=>$row['password'] ?? null], [
            'name'=>'required|string|max:255', 'email'=>'required|email|max:255', 'role'=>'required|string|exists:roles,name',
            'address'=>'required|string|max:255', 'hire_date'=>'required|date', 'status'=>'required|in:active,inactive',
            'phone'=>['nullable','regex:/^[6-9]\d{9}$/'], 'designation'=>'nullable|string|max:255', 'hourly_rate'=>'nullable|numeric|min:0',
            'salary_name'=>'nullable|string|max:255', 'salary_amount'=>'nullable|numeric|min:0', 'salary_type'=>'nullable|string|max:50',
            'password'=>'required|string|min:6',
        ]);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $role = Role::where('name', $data['role'])->firstOrFail();
        $existing = User::where('email', $data['email'])->first();
        $password = $row['password'] ?? null;
        // Keep the denormalized role column in sync with the pivot, matching the
        // normal employee create flow.
        DB::transaction(function () use (&$existing, $data, $password, $role): void {
            if ($existing) { unset($data['email']); if (blank($password)) unset($data['password']); else $data['password'] = Hash::make($password); $existing->update($data); }
            else { $data['password'] = Hash::make($password); $existing = User::create($data); }
            $existing->roles()->sync([$role->id]);
        });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
