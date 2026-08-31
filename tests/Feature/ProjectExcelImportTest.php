<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\ProjectImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProjectExcelImportTest extends TestCase
{
    use RefreshDatabase;
    private User $actor;
    private Client $client;
    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->create();
        $this->client = Client::create(['name'=>'Acme Client','email'=>'client@example.com','status'=>'active']);
    }

    public function test_successful_import_resolves_client_and_records_history(): void
    {
        $result = $this->import([['project_code','client','name','type','priority','status'], ['P-001','Acme Client','Site One','Construction','high','planning']]);
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('projects', ['project_code'=>'P-001','client_id'=>$this->client->id]);
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id,'module'=>'projects','failed_rows'=>0]);
    }

    public function test_unknown_client_and_invalid_project_data_are_reported(): void
    {
        $result = $this->import([['project_code','client','name','type','priority','status'], ['P-002','Unknown','Bad','Construction','urgent','wrong']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_duplicate_project_code_updates_existing_project(): void
    {
        Project::create(['project_code'=>'P-003','client_id'=>$this->client->id,'name'=>'Old','type'=>'Construction','priority'=>'low','status'=>'planning']);
        $result = $this->import([['project_code','client','name','type','priority','status'], ['P-003','client@example.com','Updated','Construction','high','active']]);
        $this->assertSame('completed', $result->status);
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', ['project_code'=>'P-003','name'=>'Updated','priority'=>'high','status'=>'active']);
    }

    public function test_mixed_rows_report_row_level_errors_and_counts(): void
    {
        $result = $this->import([['project_code','client','name','type','priority','status'], ['P-004','Acme Client','Good','Construction','medium','active'], ['P-005','Missing','Bad','Construction','medium','active']]);
        $this->assertSame('completed_with_errors', $result->status);
        $this->assertSame(2, $result->total_rows);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(1, $result->failed_rows);
        $this->assertSame(3, $result->errors[0]['row']);
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'project-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new ProjectImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
