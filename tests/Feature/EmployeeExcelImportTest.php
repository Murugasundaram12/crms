<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Excel\ExcelImportService;
use App\Services\Excel\Imports\EmployeeImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->create();
        $this->role = Role::create(['name' => 'Site Engineer']);
    }

    public function test_import_creates_user_assigns_role_and_records_history(): void
    {
        $result = $this->import([['name','email','role','address','hire_date','status','password'], ['Jane','jane@example.com','Site Engineer','Office','2026-01-01','active','secret123']]);
        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('completed', $result->status);
        $this->assertSame(1, $result->imported_rows);
        $this->assertTrue($user->roles->contains($this->role));
        $this->assertDatabaseHas('excel_imports', ['id'=>$result->id, 'module'=>'employees', 'failed_rows'=>0]);
    }

    public function test_invalid_row_is_reported(): void
    {
        $result = $this->import([['name','email','role','address','hire_date','status','password'], ['Bad','not-email','Missing Role','','bad-date','wrong','123']]);
        $this->assertSame('failed', $result->status);
        $this->assertSame(1, $result->failed_rows);
        $this->assertSame(2, $result->errors[0]['row']);
    }

    public function test_duplicate_email_updates_user_and_synchronizes_role(): void
    {
        $otherRole = Role::create(['name' => 'Manager']);
        $user = User::factory()->create(['email'=>'jane@example.com','name'=>'Old']);
        $user->roles()->attach($otherRole);
        $result = $this->import([['name','email','role','address','hire_date','status','password'], ['New','jane@example.com','Site Engineer','Office','2026-01-01','inactive','newsecret']]);
        $user->refresh();
        $this->assertSame('completed', $result->status);
        $this->assertSame('New', $user->name);
        $this->assertSame(['Site Engineer'], $user->roles->pluck('name')->all());
        $this->assertTrue(password_verify('newsecret', $user->password));
    }

    private function import(array $rows): object
    {
        $sheet = new Spreadsheet(); $sheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'employee-import-').'.xlsx'; (new Xlsx($sheet))->save($path);
        $result = app(ExcelImportService::class)->run($path, basename($path), $this->actor->id, new EmployeeImport());
        @unlink($path); $sheet->disconnectWorksheets(); return $result;
    }
}
