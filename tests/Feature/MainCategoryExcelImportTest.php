<?php

namespace Tests\Feature;

use App\Models\MainCategory;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\MainCategoryImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MainCategoryExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); }

    public function test_successful_import_defaults_active_and_records_history(): void
    {
        $result = $this->import([['name'], ['Materials']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('main_categories', ['name'=>'MATERIALS','status'=>'active']);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'main_categories','failed_rows'=>0]);
    }

    public function test_invalid_status_is_reported(): void
    {
        $result = $this->import([['name','status'], ['Bad','disabled']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseMissing('main_categories', ['name'=>'BAD']);
    }

    public function test_duplicate_name_is_case_insensitive_and_updates(): void
    {
        MainCategory::create(['name'=>'MATERIALS','status'=>'active']);
        $result = $this->import([['name','status'], [' materials ','inactive']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, MainCategory::where('name', 'MATERIALS')->count());
        $this->assertDatabaseHas('main_categories', ['name'=>'MATERIALS','status'=>'inactive']);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_category(): void
    {
        $result = $this->import([['name','status'], ['Good','active'], ['Bad','invalid']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('main_categories', ['name'=>'GOOD']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'main-category-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new MainCategoryImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
