<?php

namespace App\Services\Excel\Imports;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Services\Excel\ExcelImportDefinition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectImport implements ExcelImportDefinition
{
    public function module(): string { return 'projects'; }
    public function requiredHeaders(): array { return ['projectcode','client','name','type','priority','status']; }

    public function importRow(array $row, int $rowNumber): string
    {
        $data = ['project_code'=>$row['projectcode'] ?? null, 'name'=>$row['name'] ?? null, 'type'=>$row['type'] ?? null,
            'priority'=>$row['priority'] ?? null, 'status'=>$row['status'] ?? null, 'description'=>$row['description'] ?? null,
            'progress'=>$row['progress'] ?? 0, 'start_date'=>$row['startdate'] ?? null, 'end_date'=>$row['enddate'] ?? null,
            'location'=>$row['location'] ?? null];
        $validator = Validator::make($data, ['project_code'=>'required|string|max:50','name'=>'required|string|max:255','type'=>'required|string|max:255','priority'=>'required|in:low,medium,high','status'=>'required|in:planning,active,on_hold,completed,cancelled','description'=>'nullable|string','progress'=>'nullable|integer|min:0|max:100','start_date'=>'nullable|date','end_date'=>'nullable|date|after_or_equal:start_date','location'=>'nullable|url|max:500']);
        if ($validator->fails()) throw new \InvalidArgumentException('Validation failed: '.implode(' ', $validator->errors()->all()));
        $clientRef = trim((string) ($row['client'] ?? ''));
        $client = Client::where('name', $clientRef)->orWhere('email', $clientRef)->first();
        if (! $client) throw new \InvalidArgumentException("Client reference '{$clientRef}' was not found.");
        $data['client_id'] = $client->id;
        $managerRef = trim((string) ($row['manager'] ?? ''));
        if ($managerRef !== '') $data['manager_id'] = Employee::where('name', $managerRef)->orWhere('email', $managerRef)->value('id') ?: throw new \InvalidArgumentException("Manager reference '{$managerRef}' was not found.");
        $existing = Project::where('project_code', $data['project_code'])->first();
        DB::transaction(function () use (&$existing, $data): void { if ($existing) $existing->update($data); else $existing = Project::create($data); });
        return $existing->wasRecentlyCreated ? 'created' : 'updated';
    }
}
