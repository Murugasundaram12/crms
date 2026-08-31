<?php

namespace App\Services\Excel\Imports;

use App\Models\PaymentMethod;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentMethodImport implements ExcelImportDefinition
{
    public function module(): string { return 'payment_methods'; }
    public function requiredHeaders(): array { return ['name']; }
    public function importRow(array $row, int $rowNumber): string
    {
        $name = trim((string) ($row['name'] ?? ''));
        $code = filled($row['code'] ?? null) ? (string) $row['code'] : $name;
        $data = ['name'=>$name, 'code'=>Str::upper(Str::slug($code, '_')), 'type'=>$row['type'] ?? null, 'sort_order'=>$row['sortorder'] ?? 0, 'active_status'=>$row['activestatus'] ?? true];
        $data['active_status'] = filter_var($data['active_status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ((string) $data['active_status'] === '1');
        $validator = Validator::make($data, ['name'=>'required|string|max:255','code'=>'required|string|max:100','type'=>'nullable|string|max:100','sort_order'=>'integer|min:0','active_status'=>'boolean']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $existing = PaymentMethod::where('code', $data['code'])->first();
        $sameName = PaymentMethod::where('name', $data['name'])->when($existing, fn($q) => $q->where('id','!=',$existing->id))->first();
        if ($sameName) throw new \InvalidArgumentException("Payment method name '{$data['name']}' already exists with another code.");
        DB::transaction(function () use (&$existing, $data): void { if ($existing) $existing->update($data); else $existing = PaymentMethod::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
