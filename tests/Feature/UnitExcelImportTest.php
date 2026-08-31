<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\UnitImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UnitExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); }

    public function test_successful_import_records_history(): void
    {
        $result = $this->import([['name','code','description','active_status'], ['Square Yard','sqyd','Area', '1']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('units', ['name'=>'Square Yard','code'=>'sqyd','active_status'=>1]);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'units','failed_rows'=>0]);
    }

    public function test_validation_errors_are_reported(): void
    {
        $result = $this->import([['name','code','active_status'], ['','bad','invalid']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
    }

    public function test_duplicate_code_updates_existing_unit(): void
    {
        Unit::create(['name'=>'Old','code'=>'u1']);
        $result = $this->import([['name','code'], ['Updated','u1']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, Unit::where('code','u1')->count());
        $this->assertDatabaseHas('units', ['code'=>'u1','name'=>'Updated']);
    }

    public function test_duplicate_name_with_new_code_is_rejected(): void
    {
        Unit::create(['name'=>'Existing','code'=>'u1']);
        $result = $this->import([['name','code'], ['Existing','u2']]);
        $this->assertSame('failed', $result->status);
        $this->assertDatabaseMissing('units', ['code'=>'u2']);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_unit(): void
    {
        $result = $this->import([['name','code'], ['Good','u3'], ['Bad','']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('units', ['code'=>'u3']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'unit-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new UnitImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
