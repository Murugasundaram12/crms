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
        $errors = []; $imported = $skipped = $total = 0;
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
            $headers = array_map(fn($v) => $this->key($v), array_shift($rows) ?: []);
            $missing = array_diff($definition->requiredHeaders(), $headers);
            if ($missing) throw new \InvalidArgumentException('Missing required columns: '.implode(', ', $missing));
            DB::transaction(function () use ($rows, $headers, $definition, &$errors, &$imported, &$skipped, &$total): void {
                foreach ($rows as $offset => $values) { $rowNumber = $offset + 2; if (collect($values)->filter(fn($v)=>(string)$v !== '')->isEmpty()) continue; $total++; $row = []; foreach ($headers as $i=>$key) $row[$key] = $values[$i] ?? null; try { $result = $definition->importRow($row, $rowNumber); $result === 'skipped' ? $skipped++ : $imported++; } catch (\Throwable $e) { $errors[] = ['row'=>$rowNumber,'message'=>$e->getMessage()]; }
                }
            });
            $record->update(['status'=>$errors ? ($imported ? 'completed_with_errors' : 'failed') : 'completed','total_rows'=>$total,'imported_rows'=>$imported,'skipped_rows'=>$skipped,'failed_rows'=>count($errors),'errors'=>$errors]);
        } catch (\Throwable $e) { $errors[] = ['row'=>null,'message'=>$e->getMessage()]; $record->update(['status'=>'failed','total_rows'=>$total,'imported_rows'=>$imported,'skipped_rows'=>$skipped,'failed_rows'=>count($errors),'errors'=>$errors]); }
        return $record->fresh();
    }
    public function key($value): string { return preg_replace('/[^a-z0-9]/','',mb_strtolower(trim((string)$value))); }
    public function date($value): ?string { if ($value === null || $value === '') return null; return is_numeric($value) ? ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d') : date('Y-m-d', strtotime((string)$value)); }
}
