<?php

namespace Tests\Feature;

use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\LabourImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LabourExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    private LabourRole $role;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); $this->role = LabourRole::create(['name'=>'Mason','salary_type'=>'daily','salary'=>500]); }

    public function test_successful_import_resolves_role_and_records_history(): void
    {
        $result = $this->import([['name','phone_number','labour_role','gender','salary'], ['John','9876543210','Mason','male','500']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('labours', ['name'=>'John','labour_role_id'=>$this->role->id]);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'labours','failed_rows'=>0]);
    }

    public function test_unknown_role_and_invalid_data_are_reported(): void
    {
        $result = $this->import([['name','phone_number','labour_role','gender','salary'], ['Bad','123','Unknown','invalid','-1']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseCount('labours', 0);
    }

    public function test_duplicate_phone_updates_existing_labour(): void
    {
        Labour::create(['name'=>'Old','phone_number'=>'9876543210','labour_role_id'=>$this->role->id,'gender'=>'male','salary'=>400]);
        $result = $this->import([['name','phone_number','labour_role','gender','salary'], ['New','9876543210','Mason','female','600']]);
        $this->assertSame('completed', $result->status);
        $this->assertDatabaseCount('labours', 1);
        $this->assertDatabaseHas('labours', ['name'=>'New','salary'=>600]);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_labour(): void
    {
        $result = $this->import([['name','phone_number','labour_role','gender','salary'], ['Good','9876543210','Mason','male','500'], ['Bad','123','Missing','male','500']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('labours', ['name'=>'Good']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'labour-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new LabourImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
