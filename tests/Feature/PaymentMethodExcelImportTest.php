<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\PaymentMethodImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PaymentMethodExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    protected function setUp(): void { parent::setUp(); $this->actor = User::factory()->create(); }

    public function test_successful_import_applies_defaults_and_records_history(): void
    {
        $result = $this->import([['name','code','type','sort_order','active_status'], ['Wire Transfer','wire transfer','bank','2','1']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('payment_methods', ['name'=>'Wire Transfer','code'=>'WIRE_TRANSFER','sort_order'=>2]);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'payment_methods','failed_rows'=>0]);
    }

    public function test_validation_errors_are_reported(): void
    {
        $result = $this->import([['name','sort_order','active_status'], ['','bad','invalid']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
    }

    public function test_duplicate_code_updates_existing_method(): void
    {
        PaymentMethod::create(['name'=>'Old','code'=>'BANK','active_status'=>true,'sort_order'=>1]);
        $result = $this->import([['name','code'], ['Updated','bank']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, PaymentMethod::where('code','BANK')->count());
        $this->assertDatabaseHas('payment_methods', ['code'=>'BANK','name'=>'Updated']);
    }

    public function test_duplicate_name_with_another_code_is_rejected(): void
    {
        PaymentMethod::create(['name'=>'Custom Cash','code'=>'CUSTOM_CASH','active_status'=>true,'sort_order'=>1]);
        $result = $this->import([['name','code'], ['Custom Cash','CASH_NEW']]);
        $this->assertSame('failed', $result->status);
        $this->assertDatabaseMissing('payment_methods', ['code'=>'CASH_NEW']);
    }

    public function test_mixed_rows_report_counts_and_keep_valid_method(): void
    {
        $result = $this->import([['name','code'], ['Good','GOOD'], ['', 'BAD']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('payment_methods', ['code'=>'GOOD']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'payment-method-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new PaymentMethodImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
