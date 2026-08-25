<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\EmployeeSalary;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocationTracking;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use App\Services\EmployeePayrollService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmployeePayrollCalculationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $employee;
    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $role = Role::query()->firstOrCreate(['name' => 'Super Admin']);
        $pCreate = \App\Models\Permission::query()->firstOrCreate(['key' => 'employees-salary-create'], ['name' => 'Create Salary']);
        $pList = \App\Models\Permission::query()->firstOrCreate(['key' => 'employees-salary-list'], ['name' => 'List Salary']);
        $pEdit = \App\Models\Permission::query()->firstOrCreate(['key' => 'employees-salary-edit'], ['name' => 'Edit Salary']);
        $pDelete = \App\Models\Permission::query()->firstOrCreate(['key' => 'employees-salary-delete'], ['name' => 'Delete Salary']);
        $role->permissions()->syncWithoutDetaching([$pCreate->id, $pList->id, $pEdit->id, $pDelete->id]);

        $uniqueId = uniqid();
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_' . $uniqueId . '@example.com',
            'role' => 'Super Admin',
            'wallet' => 50000.00,
        ]);
        $this->admin->roles()->sync([$role->id]);
        $this->admin->clearResolvedPermissions();

        $this->employee = User::factory()->create([
            'name' => 'Jane Employee',
            'email' => 'jane_' . $uniqueId . '@example.com',
            'salary_amount' => 26000.00,
            'salary_type' => 'monthly',
            'status' => 'active',
        ]);

        $this->paymentMethod = PaymentMethod::query()->firstOrCreate(
            ['name' => 'Cash'],
            ['code' => 'CASH', 'type' => 'cash', 'is_active' => true]
        );
    }

    public function test_full_attendance_month_no_deduction(): void
    {
        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $endDate = Carbon::create(2026, 8, 31)->startOfDay();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY) {
                Attendance::create([
                    'user_id' => $this->employee->id,
                    'attendance_date' => $current->toDateString(),
                    'check_in_at' => $current->copy()->setTime(9, 0),
                    'check_out_at' => $current->copy()->setTime(17, 0),
                    'worked_minutes' => 480,
                    'status' => 'present',
                ]);
            }
            $current->addDay();
        }

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(26, $res['working_days']);
        $this->assertEquals(26, $res['present_days']);
        $this->assertEquals(0, $res['absent_days']);
        $this->assertEquals(0, $res['half_days']);
        $this->assertEquals(0.00, $res['attendance_deduction']);
        $this->assertEquals(26000.00, $res['net_salary']);
    }

    public function test_missing_attendance_absent_days_deduction(): void
    {
        // 26 working days in August 2026. Create 24 present days, leaving 2 absent days.
        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $endDate = Carbon::create(2026, 8, 31)->startOfDay();

        $workingDaysCount = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY) {
                $workingDaysCount++;
                if ($workingDaysCount <= 24) {
                    Attendance::create([
                        'user_id' => $this->employee->id,
                        'attendance_date' => $current->toDateString(),
                        'check_in_at' => $current->copy()->setTime(9, 0),
                        'check_out_at' => $current->copy()->setTime(17, 0),
                        'worked_minutes' => 480,
                        'status' => 'present',
                    ]);
                }
            }
            $current->addDay();
        }

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(26, $res['working_days']);
        $this->assertEquals(24, $res['present_days']);
        $this->assertEquals(2, $res['absent_days']);
        $this->assertEquals(1000.00, $res['per_day_salary']);
        $this->assertEquals(2000.00, $res['absent_deduction']);
        $this->assertEquals(24000.00, $res['net_salary']);
    }

    public function test_paid_leave_no_deduction(): void
    {
        $paidLeaveType = LeaveType::create(['name' => 'Casual Leave', 'status' => 'active']);

        // 1 day paid leave on Monday 2026-08-03
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $paidLeaveType->id,
            'from_date' => '2026-08-03',
            'to_date' => '2026-08-03',
            'status' => 'approved',
        ]);

        // Present for remaining working days
        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $endDate = Carbon::create(2026, 8, 31)->startOfDay();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY && $current->toDateString() !== '2026-08-03') {
                Attendance::create([
                    'user_id' => $this->employee->id,
                    'attendance_date' => $current->toDateString(),
                    'check_in_at' => $current->copy()->setTime(9, 0),
                    'check_out_at' => $current->copy()->setTime(17, 0),
                    'worked_minutes' => 480,
                    'status' => 'present',
                ]);
            }
            $current->addDay();
        }

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(26, $res['working_days']);
        $this->assertEquals(25, $res['present_days']);
        $this->assertEquals(1, $res['paid_leave_days']);
        $this->assertEquals(0, $res['unpaid_leave_days']);
        $this->assertEquals(0, $res['absent_days']);
        $this->assertEquals(0.00, $res['attendance_deduction']);
        $this->assertEquals(26000.00, $res['net_salary']);
    }

    public function test_unpaid_leave_full_deduction(): void
    {
        $unpaidLeaveType = LeaveType::create(['name' => 'Loss of Pay (LWP)', 'status' => 'active']);

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $unpaidLeaveType->id,
            'from_date' => '2026-08-03',
            'to_date' => '2026-08-03',
            'status' => 'approved',
        ]);

        // Present on all other working days
        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $endDate = Carbon::create(2026, 8, 31)->startOfDay();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY && $current->toDateString() !== '2026-08-03') {
                Attendance::create([
                    'user_id' => $this->employee->id,
                    'attendance_date' => $current->toDateString(),
                    'check_in_at' => $current->copy()->setTime(9, 0),
                    'check_out_at' => $current->copy()->setTime(17, 0),
                    'worked_minutes' => 480,
                    'status' => 'present',
                ]);
            }
            $current->addDay();
        }

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(26, $res['working_days']);
        $this->assertEquals(25, $res['present_days']);
        $this->assertEquals(1, $res['unpaid_leave_days']);
        $this->assertEquals(1000.00, $res['unpaid_leave_deduction']);
        $this->assertEquals(25000.00, $res['net_salary']);
    }

    public function test_half_day_deduction(): void
    {
        // 1 half-day (200 worked_minutes < 240 threshold), rest full days
        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $endDate = Carbon::create(2026, 8, 31)->startOfDay();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY) {
                $minutes = ($current->toDateString() === '2026-08-03') ? 200 : 480;
                Attendance::create([
                    'user_id' => $this->employee->id,
                    'attendance_date' => $current->toDateString(),
                    'check_in_at' => $current->copy()->setTime(9, 0),
                    'check_out_at' => $current->copy()->setTime(12, 20),
                    'worked_minutes' => $minutes,
                    'status' => 'present',
                ]);
            }
            $current->addDay();
        }

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(26, $res['working_days']);
        $this->assertEquals(25, $res['present_days']);
        $this->assertEquals(1, $res['half_days']);
        $this->assertEquals(500.00, $res['half_day_deduction']);
        $this->assertEquals(25500.00, $res['net_salary']);
    }

    public function test_sunday_weekoff_ignored_from_deductions(): void
    {
        // No attendance records created on Sundays. Sundays must not be counted in working_days or absent_days.
        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(5, $res['weekoff_days']);
        $this->assertEquals(26, $res['working_days']);
    }

    public function test_incomplete_checkin_without_checkout(): void
    {
        // Check-in without check-out (worked_minutes is null). Should be treated as full present day or handled safely.
        Attendance::create([
            'user_id' => $this->employee->id,
            'attendance_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 09:00:00',
            'check_out_at' => null,
            'worked_minutes' => null,
            'status' => 'present',
        ]);

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(1, $res['present_days']);
        $this->assertEquals(0, $res['half_days']);
    }

    public function test_attendance_overlapping_leave_handling(): void
    {
        $paidLeaveType = LeaveType::create(['name' => 'Sick Leave', 'status' => 'active']);

        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $paidLeaveType->id,
            'from_date' => '2026-08-03',
            'to_date' => '2026-08-03',
            'status' => 'approved',
        ]);

        $startDate = Carbon::create(2026, 8, 1)->startOfDay();
        $endDate = Carbon::create(2026, 8, 31)->startOfDay();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->dayOfWeek !== Carbon::SUNDAY) {
                Attendance::create([
                    'user_id' => $this->employee->id,
                    'attendance_date' => $current->toDateString(),
                    'check_in_at' => $current->copy()->setTime(9, 0),
                    'check_out_at' => $current->copy()->setTime(17, 0),
                    'worked_minutes' => 480,
                    'status' => 'present',
                ]);
            }
            $current->addDay();
        }

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(1, $res['paid_leave_days']);
        $this->assertEquals(0, $res['absent_days']);
        $this->assertEquals(0.00, $res['attendance_deduction']);
    }

    public function test_period_with_no_attendance_all_working_days_absent(): void
    {
        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(26, $res['working_days']);
        $this->assertEquals(0, $res['present_days']);
        $this->assertEquals(26, $res['absent_days']);
        $this->assertEquals(26000.00, $res['absent_deduction']);
        $this->assertEquals(0.00, $res['net_salary']);
    }

    public function test_different_monthly_salaries(): void
    {
        $this->employee->update(['salary_amount' => 52000.00]);

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(52000.00, $res['monthly_salary']);
        $this->assertEquals(2000.00, $res['per_day_salary']);
    }

    public function test_wallet_debit_only_occurs_on_actual_payment(): void
    {
        $initialWallet = (float) $this->admin->wallet;

        // Step 1: Calculate payroll via AJAX endpoint - wallet must NOT change
        $calcResponse = $this->actingAs($this->admin)->postJson(route('employee-salaries.calculate'), [
            'user_id' => $this->employee->id,
            'salary_period' => 'August 2026',
        ]);

        $calcResponse->assertStatus(200);
        $calcResponse->assertJsonPath('success', true);
        $this->assertEquals($initialWallet, (float) $this->admin->fresh()->wallet);

        // Step 2: Save salary record with paid_amount = 5000.00 - wallet MUST debit by 5000.00
        $storeResponse = $this->actingAs($this->admin)->post(route('employee-salaries.store'), [
            'user_id' => $this->employee->id,
            'salary_period' => 'August 2026',
            'salary_amount' => 26000.00,
            'paid_amount' => 5000.00,
            'payment_date' => '2026-08-25',
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Partial payroll payment',
        ]);

        $storeResponse->assertRedirect(route('employee-salaries.index'));
        $this->assertEquals($initialWallet - 5000.00, (float) $this->admin->fresh()->wallet);

        $this->assertDatabaseHas('employee_salaries', [
            'user_id' => $this->employee->id,
            'salary_period' => 'August 2026',
            'salary_amount' => 26000.00,
            'paid_amount' => 5000.00,
            'remaining_amount' => 21000.00,
        ]);
    }

    public function test_gps_tracking_isolation(): void
    {
        $att = Attendance::create([
            'user_id' => $this->employee->id,
            'attendance_date' => '2026-08-03',
            'check_in_at' => '2026-08-03 09:00:00',
            'check_out_at' => '2026-08-03 17:00:00',
            'worked_minutes' => 480,
            'status' => 'present',
        ]);

        // Add GPS tracking points
        LocationTracking::create([
            'attendance_id' => $att->id,
            'employee_id' => $this->employee->id,
            'latitude' => 9.9252,
            'longitude' => 78.1198,
            'speed' => 12.5,
            'recorded_at' => now(),
        ]);

        $service = app(EmployeePayrollService::class);
        $res = $service->calculateMonthlySalary($this->employee, 'August 2026');

        $this->assertEquals(1, $res['present_days']);
        $this->assertEquals(26, $res['working_days']);
    }

    public function test_remaining_amount_calculation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('employee-salaries.store'), [
            'user_id' => $this->employee->id,
            'salary_period' => 'August 2026',
            'salary_amount' => 24000.00,
            'paid_amount' => 10000.00,
            'payment_date' => '2026-08-25',
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertRedirect(route('employee-salaries.index'));
        $this->assertDatabaseHas('employee_salaries', [
            'user_id' => $this->employee->id,
            'salary_amount' => 24000.00,
            'paid_amount' => 10000.00,
            'remaining_amount' => 14000.00,
            'status' => 'partial',
        ]);
    }
}
