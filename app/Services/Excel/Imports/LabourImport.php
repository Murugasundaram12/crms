<?php

namespace App\Services\Excel\Imports;

use App\Models\Labour;
use App\Models\LabourRole;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LabourImport implements ExcelImportDefinition
{
    public function module(): string { return 'labours'; }
    public function requiredHeaders(): array { return ['name', 'phonenumber', 'labourrole', 'gender', 'salary']; }

    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'job_title'=>$row['jobtitle'] ?? null, 'phone_number'=>$row['phonenumber'] ?? null,
            'gender'=>$row['gender'] ?? null, 'salary'=>$row['salary'] ?? null, 'advance_amt'=>$row['advanceamt'] ?? 0];
        $validator = Validator::make($data, ['name'=>'required|string|max:255','job_title'=>'nullable|string|max:255','phone_number'=>['required','regex:/^[6-9]\d{9}$/'],'gender'=>'required|in:male,female,other','salary'=>'required|numeric','advance_amt'=>'nullable|numeric|min:0']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $roleName = trim((string) ($row['labourrole'] ?? ''));
        $role = LabourRole::where('name', $roleName)->first();
        if (! $role) throw new \InvalidArgumentException("Labour role '{$roleName}' was not found.");
        $data['labour_role_id'] = $role->id;
        $existing = Labour::withTrashed()->where('phone_number', $data['phone_number'])->first();
        DB::transaction(function () use (&$existing, $data): void { if ($existing) { if ($existing->trashed()) $existing->restore(); $existing->update($data); } else $existing = Labour::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
