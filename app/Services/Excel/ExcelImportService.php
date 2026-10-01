<?php
namespace App\Services\Excel;

use App\Models\ExcelImport;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ExcelImportService
{
    public function create(string $module, string $filename, int $userId): ExcelImport
    {
        return ExcelImport::create(['module'=>$module,'filename'=>$filename,'user_id'=>$userId,'status'=>'processing']);
    }

    public function finish(ExcelImport $record, string $status, int $total, int $imported, int $skipped, array $errors): ExcelImport
    {
        $record->update(['status'=>$status,'total_rows'=>$total,'imported_rows'=>$imported,'skipped_rows'=>$skipped,'failed_rows'=>count($errors),'errors'=>$errors]);
        return $record->fresh();
    }

    public function run(string $path, string $filename, int $userId, ExcelImportDefinition $definition): ExcelImport
    {
        $record = ExcelImport::create(['module'=>$definition->module(),'filename'=>$filename,'user_id'=>$userId,'status'=>'processing']);
        $errors = []; $imported = $updated = $skipped = $total = 0;
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
            $headers = array_map(fn($v) => $this->key($v), array_shift($rows) ?: []);
            $aliases = [
                'fullname'=>'name','emailaddress'=>'email','mail'=>'email',
                'company'=>'companyname','clientstatus'=>'status','customerstatus'=>'status','remarks'=>'notes','remark'=>'notes',
                'projectcode'=>'projectcode','rolename'=>'role','hiredate'=>'hiredate','joiningdate'=>'hiredate','startdate'=>'startdate','enddate'=>'enddate',
                'salarytype'=>'salarytype','jobtitle'=>'jobtitle','labourrole'=>'labourrole','labourrolename'=>'labourrole',
                'itemtype'=>'itemtype','itemname'=>'name','sortorder'=>'sortorder','active'=>'activestatus','isactive'=>'activestatus',
            ];
            if (method_exists($definition, 'aliases')) $aliases = array_merge($aliases, $definition->aliases());
            $headers = array_map(fn($header) => $aliases[$header] ?? $header, $headers);
            $missing = method_exists($definition, 'requiredHeaderGroups')
                ? array_values(array_filter(array_map(function (array $group) use ($headers) {
                    return array_intersect($group, $headers) ? null : $group[0];
                }, $definition->requiredHeaderGroups())))
                : array_diff($definition->requiredHeaders(), $headers);
            if ($missing) throw new \InvalidArgumentException('Missing required columns: '.implode(', ', $missing));
            DB::transaction(function () use ($rows, $headers, $definition, &$errors, &$imported, &$updated, &$skipped, &$total): void {
                foreach ($rows as $offset => $values) { $rowNumber = $offset + 2; if (collect($values)->filter(fn($v)=>(string)$v !== '')->isEmpty()) continue; $total++; $row = []; foreach ($headers as $i=>$key) $row[$key] = $values[$i] ?? null; try { $result = $definition->importRow($row, $rowNumber); $result === 'skipped' ? $skipped++ : ($result === 'updated' ? $updated++ : $imported++); } catch (\Throwable $e) { $errors[] = ['row'=>$rowNumber,'message'=>$e->getMessage()]; }
                }
            });
            $record->update(['status'=>$errors ? (($imported + $updated) ? 'completed_with_errors' : 'failed') : 'completed','total_rows'=>$total,'imported_rows'=>$imported + $updated,'skipped_rows'=>$skipped,'failed_rows'=>count($errors),'errors'=>$errors]);
        } catch (\Throwable $e) { $errors[] = ['row'=>null,'message'=>$e->getMessage()]; $record->update(['status'=>'failed','total_rows'=>$total,'imported_rows'=>$imported,'skipped_rows'=>$skipped,'failed_rows'=>count($errors),'errors'=>$errors]); }
        $record = $record->fresh();
        $record->setAttribute('updated_rows', $updated);
        $record->setAttribute('created_rows', $imported);
        return $record;
    }
    public function key($value): string { return preg_replace('/[^a-z0-9]/','',mb_strtolower(trim((string)$value))); }
    public function date($value): ?string { if ($value === null || $value === '') return null; return is_numeric($value) ? ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d') : date('Y-m-d', strtotime((string)$value)); }
}
