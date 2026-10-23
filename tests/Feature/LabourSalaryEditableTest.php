<?php

namespace Tests\Feature;

use App\Http\Controllers\LabourAttendanceController;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class LabourSalaryEditableTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private LabourRole $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'labour_salary_' . uniqid() . '@example.com',
        ]);
        $this->actingAs($this->admin);

        $this->role = LabourRole::create([
            'name' => 'Salary Test Role ' . uniqid(),
            'salary_type' => 'daily',
            'salary' => 500,
        ]);

        if (! Schema::hasTable('labour_attendances')) {
            Schema::create('labour_attendances', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('labour_id');
                $table->unsignedBigInteger('employee_id');
                $table->date('attendance_date');
                $table->string('status')->default('present');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('labour_salary_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_edit_salary_is_saved_and_other_labour_is_unchanged(): void
    {
        $labour = $this->makeLabour('First', 500);
        $otherLabour = $this->makeLabour('Second', 500);

        $this->get(route('labours.edit', $labour->id))
            ->assertOk()
            ->assertSee('value="500.00"')
            ->assertDontSee('value="500.00" readonly');

        $response = $this->post(route('labours.update', $labour->id), $this->validUpdateData($labour, 725));

        $response->assertRedirect(route('labours.index'));
        $this->assertSame('725.00', $labour->fresh()->salary);
        $this->assertSame('500.00', $otherLabour->fresh()->salary);
    }

    public function test_negative_and_non_numeric_salary_values_are_rejected(): void
    {
        $labour = $this->makeLabour('Validated', 500);

        foreach ([-1, 'not-a-number'] as $invalidSalary) {
            $this->from(route('labours.edit', $labour->id))
                ->post(route('labours.update', $labour->id), $this->validUpdateData($labour, $invalidSalary))
                ->assertRedirect(route('labours.edit', $labour->id))
                ->assertSessionHasErrors('salary');
        }

        $this->assertSame('500.00', $labour->fresh()->salary);
    }

    public function test_attendance_salary_uses_each_labours_saved_salary(): void
    {
        $first = $this->makeLabour('Daily First', 700);
        $second = $this->makeLabour('Daily Second', 1100);

        LabourAttendance::create([
            'labour_id' => $first->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-10-19',
            'status' => 'present',
        ]);
        LabourAttendance::create([
            'labour_id' => $second->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-10-19',
            'status' => 'present',
        ]);

        $firstSummary = LabourAttendanceController::calculatePeriodSummary($first, '2026-10-19', '2026-10-19');
        $secondSummary = LabourAttendanceController::calculatePeriodSummary($second, '2026-10-19', '2026-10-19');

        $this->assertSame(700.0, $firstSummary['base_rate']);
        $this->assertSame(700.0, $firstSummary['calculated_salary']);
        $this->assertSame(1100.0, $secondSummary['base_rate']);
        $this->assertSame(1100.0, $secondSummary['calculated_salary']);
    }

    private function makeLabour(string $name, float $salary): Labour
    {
        return Labour::create([
            'name' => $name . ' ' . uniqid(),
            'phone' => '9876543210',
            'phone_number' => '9876543210',
            'labour_role_id' => $this->role->id,
            'gender' => 'male',
            'salary' => $salary,
            'advance_amt' => 0,
        ]);
    }

    private function validUpdateData(Labour $labour, mixed $salary): array
    {
        return [
            'name' => $labour->name,
            'job_title' => $labour->job_title,
            'phone_number' => $labour->phone_number,
            'labour_role_id' => $labour->labour_role_id,
            'gender' => $labour->gender,
            'salary' => $salary,
            'advance_amt' => $labour->advance_amt,
        ];
    }
}
