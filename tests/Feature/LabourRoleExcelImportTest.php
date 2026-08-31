<?php

namespace Tests\Feature;

use App\Models\LabourRole;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\LabourRoleImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LabourRoleExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); }

    public function test_successful_role_import_records_history(): void
    {
        $result = $this->import([['name','salary_type','salary'], ['Mason','daily','500']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('labour_roles', ['name'=>'Mason','salary_type'=>'daily']);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'labour_roles','failed_rows'=>0]);
    }

    public function test_invalid_role_data_is_reported(): void
    {
        $result = $this->import([['name','salary_type','salary'], ['Bad','hourly','-1']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseCount('labour_roles', 0);
    }

    public function test_duplicate_name_updates_existing_role(): void
    {
        LabourRole::create(['name'=>'Mason','salary_type'=>'daily','salary'=>400]);
        $result = $this->import([['name','salary_type','salary'], ['Mason','monthly','12000']]);
        $this->assertSame('completed', $result->status);
        $this->assertDatabaseCount('labour_roles', 1);
        $this->assertDatabaseHas('labour_roles', ['name'=>'Mason','salary_type'=>'monthly','salary'=>12000]);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_role(): void
    {
        $result = $this->import([['name','salary_type','salary'], ['Good','weekly','3500'], ['Bad','hourly','0']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('labour_roles', ['name'=>'Good']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'labour-role-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new LabourRoleImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
