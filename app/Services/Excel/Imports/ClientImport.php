<?php
namespace App\Services\Excel\Imports;
use App\Models\Client;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\Validator;

class ClientImport implements ExcelImportDefinition
{
    public function module(): string { return 'clients'; }
    public function requiredHeaders(): array { return ['name','phone','status']; }
    public function importRow(array $row, int $rowNumber): string
    {
        $data = array_intersect_key($row, array_flip(['name','companyname','email','phone','address','city','state','country','status','notes']));
        $data['company_name'] = $row['companyname'] ?? null;
        unset($data['companyname']);
        $validator = Validator::make($data, ['name'=>'required|string|max:255','company_name'=>'nullable|string|max:255','email'=>'nullable|email|max:255','phone'=>['required','regex:/^[6-9]\d{9}$/'],'address'=>'nullable|string|max:255','city'=>'nullable|string|max:100','state'=>'nullable|string|max:100','country'=>'nullable|string|max:100','status'=>'required|in:enquiry,active,inactive','notes'=>'nullable|string']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $existing = Client::where('phone',$data['phone'])->first();
        if ($existing) { $existing->update($data); return 'updated'; }
        Client::create($data); return 'created';
    }
}
