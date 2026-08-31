<?php

namespace App\Services\Excel\Imports;

use App\Models\Category;
use App\Models\MainCategory;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryImport implements ExcelImportDefinition
{
    public function module(): string { return 'categories'; }
    public function requiredHeaders(): array { return ['name', 'maincategory']; }
    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'main_category_id'=>null];
        $validator = Validator::make(['name'=>$data['name']], ['name'=>'required|string|max:255']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $mainName = trim((string) ($row['maincategory'] ?? ''));
        $main = MainCategory::whereRaw('UPPER(name) = ?', [mb_strtoupper($mainName)])->first();
        if (! $main) throw new \InvalidArgumentException("Main category reference '{$mainName}' was not found.");
        $data['name'] = mb_strtoupper(trim((string) $data['name']));
        $data['main_category_id'] = $main->id;
        $existing = Category::whereRaw('UPPER(name) = ?', [$data['name']])->first();
        DB::transaction(function () use (&$existing, $data): void {
            if ($existing) { $existing->update($data); $existing->mainCategories()->sync([$data['main_category_id']]); }
            else { $existing = Category::create($data); $existing->mainCategories()->sync([$data['main_category_id']]); }
        });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
