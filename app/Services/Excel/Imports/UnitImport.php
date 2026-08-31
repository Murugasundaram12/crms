<?php

namespace App\Services\Excel\Imports;

use App\Models\Unit;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnitImport implements ExcelImportDefinition
{
    public function module(): string { return 'units'; }
    public function requiredHeaders(): array { return ['name', 'code']; }
    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'code'=>$row['code'] ?? null, 'description'=>$row['description'] ?? null, 'active_status'=>$row['activestatus'] ?? true];
        $data['active_status'] = filter_var($data['active_status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ((string) $data['active_status'] === '1');
        $validator = Validator::make($data, ['name'=>'required|string|max:100','code'=>'required|string|max:50','description'=>'nullable|string|max:255','active_status'=>'boolean']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $existing = Unit::where('code', trim($data['code']))->first();
        $sameName = Unit::where('name', trim($data['name']))->when($existing, fn($q) => $q->where('id', '!=', $existing->id))->first();
        if ($sameName) throw new \InvalidArgumentException("Unit name '{$data['name']}' already exists with another code.");
        $data['name'] = trim($data['name']); $data['code'] = trim($data['code']);
        DB::transaction(function () use (&$existing, $data): void { if ($existing) $existing->update($data); else $existing = Unit::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
