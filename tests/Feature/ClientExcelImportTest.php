<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ExcelImport;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\ClientImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ClientExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_successful_xlsx_import_creates_client_and_history(): void
    {
        $result = $this->import([
            ['name', 'phone', 'status', 'email'],
            ['Acme', '9876543210', 'active', 'acme@example.com'],
        ], 'xlsx');

        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(0, $result->failed_rows);
        $this->assertDatabaseHas('clients', ['phone' => '9876543210', 'name' => 'Acme']);
        $this->assertDatabaseHas('excel_imports', ['id' => $result->id, 'module' => 'clients']);
    }

    public function test_missing_required_header_fails_without_importing(): void
    {
        $result = $this->import([
            ['name', 'phone'],
            ['Acme', '9876543210'],
        ]);

        $this->assertSame('failed', $result->status);
        $this->assertSame(0, $result->imported_rows);
        $this->assertStringContainsString('status', $result->errors[0]['message']);
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_invalid_rows_are_reported_and_not_inserted(): void
    {
        $result = $this->import([
            ['name', 'phone', 'status'],
            ['Bad phone', '123', 'active'],
        ]);

        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertSame(2, $result->errors[0]['row']);
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_duplicate_phone_updates_existing_client(): void
    {
        Client::create(['name' => 'Old Name', 'phone' => '9876543210', 'status' => 'active']);

        $result = $this->import([
            ['name', 'phone', 'status'],
            ['New Name', '9876543210', 'inactive'],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseHas('clients', ['name' => 'New Name', 'status' => 'inactive']);
    }

    public function test_mixed_rows_store_success_and_row_error_counts(): void
    {
        $result = $this->import([
            ['name', 'phone', 'status'],
            ['Good', '9876543210', 'active'],
            ['Bad', '123', 'active'],
        ]);

        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseHas('clients', ['name' => 'Good']);
    }

    public function test_csv_format_is_supported(): void
    {
        $result = $this->import([
            ['name', 'phone', 'status'],
            ['CSV Client', '9876543211', 'enquiry'],
        ], 'csv');

        $this->assertSame('completed', $result->status);
        $this->assertDatabaseHas('clients', ['name' => 'CSV Client']);
    }

    private function import(array $rows, string $format = 'xlsx'): ExcelImport
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'crms-import-').'.'.$format;
        $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->user->id, new ClientImport());
        @unlink($path);
        $spreadsheet->disconnectWorksheets();
        return $result;
    }
}
