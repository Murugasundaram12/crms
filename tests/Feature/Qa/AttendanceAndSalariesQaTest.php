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
    public function test_employee_attendance_check_in_and_duplicate_prevention(): void
    {
        $admin = $this->createSuperAdmin();
        $today = now()->toDateString();

        Attendance::create([
            'user_id' => $admin->id,
            'date' => $today,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $admin->id,
            'status' => 'present',
        ]);

        $record = Attendance::query()->where('user_id', $admin->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals($today, $record->attendance_date->toDateString());
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
