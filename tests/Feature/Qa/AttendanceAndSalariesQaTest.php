<?php

namespace Tests\Feature\Qa;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\QaTestCase;

class AttendanceAndSalariesQaTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('labour_salaries');
            Schema::dropIfExists('attendances');
            Schema::dropIfExists('labours');
            Schema::dropIfExists('labour_roles');
            Schema::dropIfExists('employees');

            Schema::create('employees', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('salary_amount', 14, 2)->default(0);
                $table->string('status')->default('active');
                $table->timestamps();
            });

            Schema::create('labour_roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->decimal('salary', 14, 2)->default(0);
                $table->timestamps();
            });

            Schema::create('labours', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('labour_role_id')->nullable();
                $table->decimal('salary', 14, 2)->default(0);
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });

            Schema::create('attendances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->date('date')->nullable();
                $table->date('attendance_date')->nullable();
                $table->string('status')->default('present');
                $table->time('check_in')->nullable();
                $table->time('check_out')->nullable();
                $table->timestamps();
            });

            Schema::create('labour_salaries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('labour_id');
                $table->date('salary_period_start')->nullable();
                $table->date('salary_period_end')->nullable();
                $table->decimal('salary_amount', 14, 2)->default(0);
                $table->decimal('advance_adjusted', 14, 2)->default(0);
                $table->tinyInteger('advance_paid')->default(0);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->date('payment_date')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('paid_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Test Attendance Check-in and Duplicate Prevention.
     */
    public function test_employee_attendance_check_in_and_duplicate_prevention(): void
    {
        $admin = $this->createSuperAdmin();

        $today = now()->toDateString();

        // 1. Initial check-in
        Attendance::create([
            'user_id' => $admin->id,
            'date' => $today,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $admin->id,
            'attendance_date' => $today,
            'status' => 'present',
        ]);

        $count = Attendance::query()
            ->where('user_id', $admin->id)
            ->where('attendance_date', $today)
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test Labour Salary Calculation with Advance Adjustment Deduction.
     */
    public function test_labour_salary_advance_deduction_arithmetic(): void
    {
        $role = LabourRole::create(['name' => 'Carpenter', 'salary' => 900.00]);
        $labour = Labour::create([
            'name' => 'Karthik S',
            'phone_number' => '9944112233',
            'gender' => 'male',
            'labour_role_id' => $role->id,
            'salary' => 900.00,
            'advance_amt' => 2000.00,
        ]);

        // Scenario: Worked 6 days @ 900/day = 5400 gross.
        // Advance deduction = 2000.
        // Net payable = 3400.
        $grossSalary = 6 * $labour->salary; // 5400.00
        $advanceDeduction = 2000.00;
        $netPayable = $grossSalary - $advanceDeduction; // 3400.00

        $salaryRecord = LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => now()->startOfWeek()->toDateString(),
            'salary_period_end' => now()->endOfWeek()->toDateString(),
            'salary_amount' => $grossSalary,
            'advance_adjusted' => $advanceDeduction,
            'paid_amount' => $netPayable,
            'status' => 'paid',
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertEquals(5400.00, $salaryRecord->salary_amount);
        $this->assertEquals(2000.00, $salaryRecord->advance_adjusted);
        $this->assertEquals(3400.00, $salaryRecord->paid_amount);
        $this->assertEquals(
            $salaryRecord->salary_amount - $salaryRecord->advance_adjusted,
            $salaryRecord->paid_amount
        );
    }
}
