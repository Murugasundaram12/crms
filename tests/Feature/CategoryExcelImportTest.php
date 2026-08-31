<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MainCategory;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\CategoryImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CategoryExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    private MainCategory $main;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); $this->main = MainCategory::create(['name'=>'Materials','status'=>'active']); }

    public function test_successful_import_resolves_main_category_and_history(): void
    {
        $result = $this->import([['name','main_category'], ['Cement','Materials']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('categories', ['name'=>'CEMENT','main_category_id'=>$this->main->id]);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'categories','failed_rows'=>0]);
    }

    public function test_unknown_main_category_is_reported(): void
    {
        $result = $this->import([['name','main_category'], ['Cement','Unknown']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseMissing('categories', ['name'=>'CEMENT']);
    }

    public function test_validation_error_is_reported(): void
    {
        $result = $this->import([['name','main_category'], ['', 'Materials']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
    }

    public function test_duplicate_name_updates_main_category_assignment(): void
    {
        $other = MainCategory::create(['name'=>'Tools','status'=>'active']);
        Category::create(['name'=>'CEMENT','main_category_id'=>$other->id]);
        $result = $this->import([['name','main_category'], [' cement ','Materials']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, Category::where('name','CEMENT')->count());
        $this->assertDatabaseHas('categories', ['name'=>'CEMENT','main_category_id'=>$this->main->id]);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_category(): void
    {
        $result = $this->import([['name','main_category'], ['Good','Materials'], ['Bad','Missing']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('categories', ['name'=>'GOOD']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'category-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new CategoryImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
