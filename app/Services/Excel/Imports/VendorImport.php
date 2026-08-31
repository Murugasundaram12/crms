<?php

namespace App\Services\Excel\Imports;

use App\Models\Vendor;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class VendorImport implements ExcelImportDefinition
{
    public function module(): string { return 'vendors'; }
    public function requiredHeaders(): array { return ['name']; }

    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['name'=>$row['name'] ?? null, 'address'=>$row['address'] ?? null, 'phone'=>$row['phone'] ?? null, 'advance_amount'=>$row['advanceamount'] ?? 0];
        $validator = Validator::make($data, ['name'=>'required|string|max:255','address'=>'nullable|string|max:1000','phone'=>['nullable','regex:/^[6-9]\d{9}$/'],'advance_amount'=>'nullable|numeric|min:0']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        if (Schema::hasColumn('vendors', 'advance_amt')) { $data['advance_amt'] = $data['advance_amount'] ?? 0; }
        $existing = Vendor::where('name', $data['name'])->first();
        DB::transaction(function () use (&$existing, $data): void { if ($existing) $existing->update($data); else $existing = Vendor::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
