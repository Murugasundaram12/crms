<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\VendorImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class VendorExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); }

    public function test_successful_vendor_import_records_history(): void
    {
        $result = $this->import([['name','address','phone','advance_amount'], ['Vendor One','Main Road','9876543210','125.50']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('vendors', ['name'=>'Vendor One','phone'=>'9876543210']);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'vendors','failed_rows'=>0]);
    }

    public function test_validation_errors_are_reported(): void
    {
        $result = $this->import([['name','phone','advance_amount'], ['Bad','123','-1']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertSame(2, $result->errors[0]['row']);
    }

    public function test_duplicate_name_updates_existing_vendor(): void
    {
        Vendor::create(['name'=>'Existing','address'=>'Old']);
        $result = $this->import([['name','address','phone'], ['Existing','New Address','9876543210']]);
        $this->assertSame('completed', $result->status);
        $this->assertDatabaseCount('vendors', 1);
        $this->assertDatabaseHas('vendors', ['name'=>'Existing','address'=>'New Address']);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_vendor(): void
    {
        $result = $this->import([['name','phone'], ['Good','9876543210'], ['Bad','123']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('vendors', ['name'=>'Good']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'vendor-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new VendorImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
