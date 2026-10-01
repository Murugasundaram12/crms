<?php
namespace App\Services\Excel\Imports;
use App\Models\Client;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\Validator;

class ClientImport implements ExcelImportDefinition
{
    public function module(): string { return 'clients'; }
    public function requiredHeaders(): array { return ['name','phone','status']; }
    public function requiredHeaderGroups(): array
    {
        return [['name', 'firstname', 'lastname'], ['phone'], ['status', 'activestatus']];
    }
    public function aliases(): array
    {
        return ['fullname'=>'name','clientname'=>'name','customername'=>'name','company'=>'companyname','emailaddress'=>'email','mail'=>'email','mobile'=>'phone','mobilenumber'=>'phone','phonenumber'=>'phone','contactnumber'=>'phone','contactno'=>'phone','telephone'=>'phone','clientstatus'=>'status','customerstatus'=>'status','remarks'=>'notes','remark'=>'notes'];
    }
    public function importRow(array $row, int $rowNumber): string
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') $name = trim(implode(' ', array_filter([(string) ($row['firstname'] ?? ''), (string) ($row['lastname'] ?? '')], fn ($part) => trim($part) !== '')));
        $data = array_intersect_key($row, array_flip(['email','phone','address','city','state','country','status','notes']));
        $data['name'] = $name;
        $data['company_name'] = $row['companyname'] ?? null;
        $data['phone'] = $this->normalizePhone($data['phone'] ?? null);
        $data['email'] = $this->normalizeEmail($data['email'] ?? null);
        $data['status'] = $this->normalizeStatus($row);
        $validator = Validator::make($data, ['name'=>'required|string|max:255','company_name'=>'nullable|string|max:255','email'=>'nullable|email|max:255','phone'=>['required','regex:/^[6-9]\d{9}$/'],'address'=>'nullable|string|max:255','city'=>'nullable|string|max:100','state'=>'nullable|string|max:100','country'=>'nullable|string|max:100','status'=>'required|in:enquiry,active,inactive','notes'=>'nullable|string']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $byPhone = Client::where('phone',$data['phone'])->first();
        $byEmail = $data['email'] ? Client::where('email',$data['email'])->first() : null;
        if ($byPhone && $byEmail && $byPhone->id !== $byEmail->id) throw new \InvalidArgumentException('Phone and email already belong to different clients.');
        $existing = $byPhone ?: $byEmail;
        if ($existing) { $existing->update($data); return 'updated'; }
        Client::create($data); return 'created';
    }

    private function normalizePhone($value): ?string
    {
        if ($value === null || trim((string) $value) === '') return null;
        $phone = preg_replace('/\D+/', '', (string) $value);
        if (str_starts_with($phone, '91') && strlen($phone) === 12) $phone = substr($phone, 2);
        if (str_starts_with($phone, '0') && strlen($phone) === 11) $phone = substr($phone, 1);
        return $phone;
    }

    private function normalizeEmail($value): ?string
    {
        $email = strtolower(trim((string) ($value ?? '')));
        return $email === '' ? null : $email;
    }

    private function normalizeStatus(array $row): string
    {
        $status = strtolower(trim((string) ($row['status'] ?? '')));
        if ($status !== '') return $status;

        $legacyStatus = strtolower(trim((string) ($row['activestatus'] ?? '')));
        if (in_array($legacyStatus, ['1', 'true', 'yes', 'active'], true)) return 'active';
        if (in_array($legacyStatus, ['0', 'false', 'no', 'inactive'], true)) return 'inactive';
        return 'active';
    }
}
