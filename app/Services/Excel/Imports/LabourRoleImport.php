<?php

namespace App\Services\Excel\Imports;

use App\Models\LabourRole;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LabourRoleImport implements ExcelImportDefinition
{
    public function module(): string { return 'labour_roles'; }
    public function requiredHeaders(): array { return ['name', 'salarytype', 'salary']; }
    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'salary_type'=>$row['salarytype'] ?? null, 'salary'=>$row['salary'] ?? null];
        $validator = Validator::make($data, ['name'=>'required|string|max:255','salary_type'=>'required|in:daily,weekly,monthly','salary'=>'required|numeric']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $existing = LabourRole::where('name', $data['name'])->first();
        DB::transaction(function () use (&$existing, $data): void { if ($existing) $existing->update($data); else $existing = LabourRole::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
