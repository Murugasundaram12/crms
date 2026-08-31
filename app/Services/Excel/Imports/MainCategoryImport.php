<?php

namespace App\Services\Excel\Imports;

use App\Models\MainCategory;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MainCategoryImport implements ExcelImportDefinition
{
    public function module(): string { return 'main_categories'; }
    public function requiredHeaders(): array { return ['name']; }
    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'status'=>$row['status'] ?? 'active'];
        $validator = Validator::make($data, ['name'=>'required|string|max:255','status'=>'required|in:active,inactive']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $name = mb_strtoupper(trim((string) $data['name']));
        $existing = MainCategory::whereRaw('UPPER(name) = ?', [$name])->first();
        $data['name'] = $name;
        DB::transaction(function () use (&$existing, $data): void { if ($existing) $existing->update($data); else $existing = MainCategory::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
