<?php

namespace Tests\Feature;

use App\Http\Controllers\LabourAttendanceController;
use App\Models\AdvanceHistory;
use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourAttendance;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourSalaryAttendanceExclusionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Labour $labour;
    protected Project $project;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->admin = User::factory()->create([
            'email' => 'stage3_test_' . uniqid() . '@example.com',
        ]);

        $updateData = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $updateData['role'] = 'Super Admin';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'wallet')) {
            $updateData['wallet'] = 50000.00;
        }
        if (! empty($updateData)) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $this->admin->id)->update($updateData);
            $this->admin = $this->admin->fresh();
        }

        $this->actingAs($this->admin);

        $dailyRole = LabourRole::firstOrCreate(
            ['name' => 'Daily Mason Test'],
            ['salary_type' => 'daily', 'salary' => 500.00]
        );

        $this->labour = Labour::create([
            'name' => 'Stage3 Labour ' . uniqid(),
            'phone' => '9876543210',
            'phone_number' => '9876543210',
            'labour_role_id' => $dailyRole->id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $client = \App\Models\Client::create([
            'name' => 'Test Client ' . uniqid(),
        ]);

        $this->project = Project::create([
            'client_id' => $client->id,
            'name' => 'Site Stage3 ' . uniqid(),
            'project_code' => 'PRJ-' . strtoupper(uniqid()),
            'type' => 'general',
            'status' => 'active',
        ]);

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['name' => 'Cash'],
            [
                'code' => 'CASH',
                'type' => 'Cash',
                'active_status' => 1,
                'sort_order' => 1,
            ]
        );
    }

    // Helper to seed attendances
    protected function seedAttendance(array $dates, string $status = 'present'): void
    {
        $eid = $this->admin->id;
        foreach ($dates as $date) {
            LabourAttendance::create([
                'labour_id' => $this->labour->id,
                'employee_id' => $eid,
                'attendance_date' => $date,
                'status' => $status,
            ]);
        }
    }

    // Helper to create a paid salary record
    protected function createPaidSalary(string $start, string $end, float $amount, string $status = 'paid', array $attendanceIds = []): LabourSalary
    {
        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => $start,
            'salary_period_end' => $end,
            'salary_amount' => $amount,
            'paid_amount' => $amount,
            'advance_adjusted' => 0.00,
            'remaining_amount' => 0.00,
            'payment_date' => $end,
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => $status,
            'notes' => 'Test paid salary',
        ]);

        if ($status === 'paid') {
            if (empty($attendanceIds)) {
                $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, $start, $end);
                $attendanceIds = $summary['attendance_ids'] ?? [];
            }
            if (! empty($attendanceIds)) {
                $salary->linkAttendances($attendanceIds);
            }
        }

        return $salary;
    }

    // TEST 1 — First salary payment: 07-09 to 09-09 calculates 3 payable days
    public function test_1_first_salary_payment_calculates_all_requested_days(): void
    {
        // 2026-09-07 (Mon) to 2026-09-12 (Sat)
        $this->seedAttendance([
            '2026-09-07', '2026-09-08', '2026-09-09',
            '2026-09-10', '2026-09-11', '2026-09-12',
        ]);

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-09');

        $this->assertEquals(3.0, $summary['payable_days']);
        $this->assertEquals(1500.00, $summary['calculated_salary']);
    }

    // TEST 2 — Second salary payment: 10-09 to 12-09 excludes 07-09 (already paid)
    public function test_2_second_salary_payment_excludes_previously_paid_dates(): void
    {
        $this->seedAttendance([
            '2026-09-07', '2026-09-08', '2026-09-09',
            '2026-09-10', '2026-09-11', '2026-09-12',
        ]);

        // First salary is paid for 07 to 09
        $this->createPaidSalary('2026-09-07', '2026-09-09', 1500.00, 'paid');

        // Second salary calculation for 10 to 12
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-10', '2026-09-12');

        $this->assertEquals(3.0, $summary['payable_days']);
        $this->assertEquals(1500.00, $summary['calculated_salary']);
    }

    // TEST 3 — Accidentally overlapping range: 07-09 to 12-09 excludes 07-09 and calculates only 10-12
    public function test_3_overlapping_period_calculation_excludes_already_paid_dates(): void
    {
        $this->seedAttendance([
            '2026-09-07', '2026-09-08', '2026-09-09',
            '2026-09-10', '2026-09-11', '2026-09-12',
        ]);

        // First salary is paid for 07 to 09
        $this->createPaidSalary('2026-09-07', '2026-09-09', 1500.00, 'paid');

        // User accidentally selects full range 07-09 to 12-09
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-12');

        // Must calculate ONLY 10, 11, 12 (3 days) -> Rs 1500
        $this->assertEquals(3.0, $summary['payable_days']);
        $this->assertEquals(3, $summary['present_days']);
        $this->assertEquals(1500.00, $summary['calculated_salary']);
    }

    // TEST 4 — Multiple paid periods: 01-05 paid, 08-10 paid. Range 01-15: 06 & 13 are Sundays (Weekly Off), unpaid Mon-Sat are 07,11,12,14,15 = 5 days
    public function test_4_multiple_paid_periods_are_all_safely_excluded(): void
    {
        $allDates = [];
        for ($d = 1; $d <= 15; $d++) {
            $allDates[] = sprintf('2026-09-%02d', $d);
        }
        $this->seedAttendance($allDates);

        // Paid Period A: 01 to 05
        $this->createPaidSalary('2026-09-01', '2026-09-05', 2500.00, 'paid');
        // Paid Period B: 08 to 10
        $this->createPaidSalary('2026-09-08', '2026-09-10', 1500.00, 'paid');

        // New calculation: 01 to 15
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-01', '2026-09-15');

        // Paid excluded: 01,02,03,04,05 + 08,09,10
        // Sunday Weekly Off excluded: 06, 13
        // Unpaid working days included: 07,11,12,14,15 = 5 days
        $this->assertEquals(5.0, $summary['payable_days']);
        $this->assertEquals(5, $summary['present_days']);
        $this->assertEquals(2500.00, $summary['calculated_salary']);
    }

    // TEST 5 — Paid attendance lock regression: attendance inside paid salary period cannot be edited/deleted
    public function test_5_paid_attendance_lock_regression_cannot_delete_or_edit(): void
    {
        $this->seedAttendance(['2026-09-07']);
        $att = LabourAttendance::where('labour_id', $this->labour->id)->where('attendance_date', '2026-09-07')->first();

        // Mark salary as paid covering 07-09
        $this->createPaidSalary('2026-09-07', '2026-09-09', 1500.00, 'paid');

        // Attempt to delete attendance
        $response = $this->delete(route('labour-attendances.destroy', $att));
        $response->assertSessionHas('error', 'Cannot delete attendance for a period where salary has already been paid.');

        // Attendance still exists in database
        $this->assertDatabaseHas('labour_attendances', ['id' => $att->id]);
    }

    // TEST 6 — Assignment regression: salary payment must not modify assignment
    public function test_6_salary_payment_must_not_modify_assignment(): void
    {
        $assignment = LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-12',
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        $this->seedAttendance(['2026-09-07', '2026-09-08', '2026-09-09']);

        // Process salary payment for 07 to 09
        $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-09',
            'salary_amount' => 1500.00,
            'advance_adjusted' => 0.00,
            'paid_amount' => 1500.00,
            'payment_date' => '2026-09-09',
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Salary payment 1',
        ]);

        // Verify assignment is unchanged
        $this->assertEquals('active', $assignment->fresh()->status);
        $this->assertEquals('2026-09-07', $assignment->fresh()->start_date->toDateString());
        $this->assertEquals('2026-09-12', $assignment->fresh()->end_date->toDateString());
    }

    // TEST 7 — Advance adjustment regression: Earned ₹3000, Advance ₹2000 -> Advance adjusted ₹2000, Wallet debit ₹1000
    public function test_7_advance_adjustment_regression(): void
    {
        $this->labour->update(['advance_amt' => 2000.00]);
        $initialPayerWallet = (float) $this->admin->wallet;

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-06',
            'salary_amount' => 3000.00,
            'advance_adjusted' => 2000.00,
            'paid_amount' => 1000.00,
            'payment_date' => '2026-09-06',
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Salary with advance adjustment',
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        // Advance balance reduced to 0
        $this->assertEquals(0.00, (float) $this->labour->fresh()->advance_amt);

        // Payer wallet debited by paid_amount ONLY (1000)
        $this->assertEquals($initialPayerWallet - 1000.00, (float) $this->admin->fresh()->wallet);

        // AdvanceHistory settle record created for 2000
        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $this->labour->id,
            'amount' => 2000.00,
            'entry_type' => 'settle',
        ]);

        // Wallet ledger debit created for 1000
        $this->assertDatabaseHas('wallet', [
            'user_id' => $this->admin->id,
            'amount' => 1000,
            'payment_method_id' => $this->paymentMethod->id,
            'source_type' => 'labour_salary',
        ]);
    }

    // TEST 8 — Daily salary: Verify unpaid attendance only
    public function test_8_daily_salary_calculates_unpaid_attendance_only(): void
    {
        // Daily rate is 500
        $this->seedAttendance([
            '2026-09-07', '2026-09-08', '2026-09-09',
            '2026-09-10', '2026-09-11',
        ]);

        // Pay 07 and 08
        $this->createPaidSalary('2026-09-07', '2026-09-08', 1000.00, 'paid');

        // Calculate 07 to 11
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-11');

        // 3 unpaid days (09, 10, 11) @ 500 = 1500
        $this->assertEquals(3.0, $summary['payable_days']);
        $this->assertEquals(1500.00, $summary['calculated_salary']);
    }

    // TEST 9 — Weekly salary: Verify unpaid attendance only (base / 6)
    public function test_9_weekly_salary_calculates_unpaid_attendance_only(): void
    {
        $weeklyRole = LabourRole::create([
            'name' => 'Weekly Role ' . uniqid(),
            'salary_type' => 'weekly',
            'salary' => 3000.00, // 3000 / 6 = 500/day
        ]);
        $this->labour->update(['labour_role_id' => $weeklyRole->id]);

        $this->seedAttendance([
            '2026-09-07', '2026-09-08', '2026-09-09',
            '2026-09-10', '2026-09-11', '2026-09-12',
        ]);

        // Pay 07 and 08
        $this->createPaidSalary('2026-09-07', '2026-09-08', 1000.00, 'paid');

        // Calculate 07 to 12
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour->fresh('labourRole'), '2026-09-07', '2026-09-12');

        // 4 unpaid days (09, 10, 11, 12) @ 500 = 2000
        $this->assertEquals(4.0, $summary['payable_days']);
        $this->assertEquals(2000.00, $summary['calculated_salary']);
    }

    // TEST 10 — Monthly salary: Verify unpaid attendance only while preserving working-day formula
    public function test_10_monthly_salary_preserves_working_days_formula_with_unpaid_attendance(): void
    {
        $monthlyRole = LabourRole::create([
            'name' => 'Monthly Role ' . uniqid(),
            'salary_type' => 'monthly',
            'salary' => 26000.00,
        ]);
        $this->labour->update(['labour_role_id' => $monthlyRole->id]);

        // Period: 2026-09-07 (Mon) to 2026-09-12 (Sat) = 6 calendar days, 0 Sundays, 6 working days
        // Daily rate = 26000 / 6 = 4333.3333...
        $this->seedAttendance([
            '2026-09-07', '2026-09-08', '2026-09-09',
            '2026-09-10', '2026-09-11', '2026-09-12',
        ]);

        // Pay 07, 08, 09
        $this->createPaidSalary('2026-09-07', '2026-09-09', 13000.00, 'paid');

        // Calculate 07 to 12
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour->fresh('labourRole'), '2026-09-07', '2026-09-12');

        // 3 unpaid days (10, 11, 12)
        $this->assertEquals(3.0, $summary['payable_days']);
        $this->assertEquals(6, $summary['total_working_days']);
        // 26000 / 6 * 3 = 13000.00
        $this->assertEquals(13000.00, $summary['calculated_salary']);
    }

    // TEST 11 — Sunday behavior: preserves Sunday count and non-Sunday working days
    public function test_11_sunday_behavior_preserved(): void
    {
        // 2026-09-07 (Mon) to 2026-09-13 (Sun) -> 6 working days, 1 Sunday
        $this->seedAttendance(['2026-09-07', '2026-09-08']);

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-13');

        $this->assertEquals(1, $summary['sunday_count']);
        $this->assertEquals(6, $summary['total_working_days']);
        $this->assertEquals(2.0, $summary['payable_days']);
    }

    // TEST 12 — No attendance returns ₹0 payable salary
    public function test_12_no_attendance_returns_zero_payable_salary(): void
    {
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-12');

        $this->assertEquals(0.0, $summary['payable_days']);
        $this->assertEquals(0, $summary['present_days']);
        $this->assertEquals(0.00, $summary['calculated_salary']);
    }

    // TEST 13 — All attendance already paid returns ₹0 new payable salary
    public function test_13_all_attendance_already_paid_returns_zero_payable_salary(): void
    {
        $this->seedAttendance(['2026-09-07', '2026-09-08', '2026-09-09']);

        // Entire period is paid
        $this->createPaidSalary('2026-09-07', '2026-09-09', 1500.00, 'paid');

        // Calculate same period
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-09');

        $this->assertEquals(0.0, $summary['payable_days']);
        $this->assertEquals(0, $summary['present_days']);
        $this->assertEquals(0.00, $summary['calculated_salary']);
    }

    // PARTIAL PAYMENT TEST: Partial salary does NOT mark attendance as fully paid
    public function test_partial_salary_does_not_exclude_attendance(): void
    {
        $this->seedAttendance(['2026-09-07', '2026-09-08', '2026-09-09']);

        // Salary record with status = partial
        $this->createPaidSalary('2026-09-07', '2026-09-09', 500.00, 'partial');

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-09');

        // Attendance is NOT excluded because salary was only partial
        $this->assertEquals(3.0, $summary['payable_days']);
        $this->assertEquals(1500.00, $summary['calculated_salary']);
    }

    // SAME-DAY OVERLAP EDGE CASE: Multiple paid records overlapping same date are excluded only once
    public function test_overlapping_paid_records_do_not_double_subtract(): void
    {
        $this->seedAttendance(['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10']);

        // Overlapping paid records: Period A covers 07-09, Period B covers 08-09
        $this->createPaidSalary('2026-09-07', '2026-09-09', 1500.00, 'paid');
        $this->createPaidSalary('2026-09-08', '2026-09-09', 1000.00, 'paid');

        // Calculate 07 to 10
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-10');

        // Only 10 is unpaid -> 1 payable day, no negative days or double subtraction
        $this->assertEquals(1.0, $summary['payable_days']);
        $this->assertEquals(1, $summary['present_days']);
        $this->assertEquals(500.00, $summary['calculated_salary']);
    }

    // EXACT USER SCENARIO TEST:
    // Assignment: 07/09/2026 -> 13/09/2026
    // Attendance: 07, 08, 09 Present
    // Salary paid ONLY for 07, 08, 09 (even with period 07/09 -> 13/09)
    // 1. 07-09 are locked.
    // 2. 10-13 remain editable.
    // 3. A second salary calculation excludes 07-09.
    // 4. A second salary calculation includes newly entered 10-13 attendance.
    // 5. No double payment occurs.
    public function test_exact_scenario_07_09_paid_and_10_13_pending_remain_selectable_and_payable(): void
    {
        // Assignment covers full week: Mon 07/09/2026 -> Sun 13/09/2026
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-13',
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        // Attendance entered for 07, 08, 09 (Mon, Tue, Wed)
        $this->seedAttendance(['2026-09-07', '2026-09-08', '2026-09-09']);

        // Salary is paid with broad period 07/09/2026 -> 13/09/2026,
        // but calculated and paid ONLY for the 3 present days (3 * 500 = Rs 1500)
        $salary1 = $this->createPaidSalary('2026-09-07', '2026-09-13', 1500.00, 'paid');

        // 1. PROVE: 07-09 ARE LOCKED
        $res07 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-07',
        ]));
        $res07->assertOk();
        $res07->assertSee('Salary Paid');
        $res07->assertSee('Locked');

        $res08 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-08',
        ]));
        $res08->assertOk();
        $res08->assertSee('Salary Paid');
        $res08->assertSee('Locked');

        $res09 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-09',
        ]));
        $res09->assertOk();
        $res09->assertSee('Salary Paid');
        $res09->assertSee('Locked');

        // Attempting to overwrite 07 via bulk-store fails to modify existing paid record
        $resModify07 = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-07',
            'attendances' => [
                ['labour_id' => $this->labour->id, 'status' => 'absent'],
            ],
        ]);
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present', // still present!
        ]);

        // 2. PROVE: 10-13 REMAIN EDITABLE (NOT locked as Salary Paid)
        // 10/09 (Thursday)
        $res10 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-10',
        ]));
        $res10->assertOk();
        $res10->assertDontSee('Salary Paid');
        $res10->assertSee('Save Attendance Records');

        // 11/09 (Friday)
        $res11 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-11',
        ]));
        $res11->assertOk();
        $res11->assertDontSee('Salary Paid');
        $res11->assertSee('Save Attendance Records');

        // 12/09 (Saturday)
        $res12 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-12',
        ]));
        $res12->assertOk();
        $res12->assertDontSee('Salary Paid');
        $res12->assertSee('Save Attendance Records');

        // 13/09 (Sunday - Weekly Off, but NOT locked by salary)
        $res13 = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-13',
        ]));
        $res13->assertOk();
        $res13->assertSee('Sunday - Weekly Off');
        $res13->assertDontSee('Salary Paid');

        // User enters attendance for 10, 11, 12 via bulkStore
        $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-10',
            'attendances' => [
                ['labour_id' => $this->labour->id, 'status' => 'present'],
            ],
        ])->assertSessionHasNoErrors();

        $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-11',
            'attendances' => [
                ['labour_id' => $this->labour->id, 'status' => 'present'],
            ],
        ])->assertSessionHasNoErrors();

        $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-12',
            'attendances' => [
                ['labour_id' => $this->labour->id, 'status' => 'present'],
            ],
        ])->assertSessionHasNoErrors();

        // 3 & 4. PROVE: SECOND SALARY CALCULATION EXCLUDES 07-09 AND INCLUDES 10-13
        // Even when user selects the entire week (07/09 to 13/09)
        $summary2 = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-13');

        // 07, 08, 09 are excluded (already paid by salary1)
        // 10, 11, 12 are included (unpaid) -> 3 days
        // 13 is Sunday (weekly off) -> excluded
        $this->assertEquals(3.0, $summary2['payable_days']);
        $this->assertEquals(3, $summary2['present_days']);
        $this->assertEquals(1500.00, $summary2['calculated_salary']);

        // 5. PROVE: NO DOUBLE PAYMENT OCCURS
        // Process Salary #2 for the remaining days (10 to 13)
        $salary2 = $this->createPaidSalary('2026-09-10', '2026-09-13', 1500.00, 'paid');

        // A third calculation for the entire week now returns 0 payable days
        $summary3 = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-13');
        $this->assertEquals(0.0, $summary3['payable_days']);
        $this->assertEquals(0, $summary3['present_days']);
        $this->assertEquals(0.00, $summary3['calculated_salary']);

        // Verify total amount paid across both salaries is exactly 3000 (6 working days * 500)
        $totalPaid = LabourSalary::where('labour_id', $this->labour->id)->where('status', 'paid')->sum('paid_amount');
        $this->assertEquals(3000.00, (float) $totalPaid);
    }

    /**
     * EXACT PRE-EXISTING ATTENDANCE SCENARIO
     * All 7 attendance records exist BEFORE Salary #1 is created.
     * Salary #1 covers ONLY 07, 08, 09 by explicit attendance_ids.
     * 
     * PROVES:
     * 1. 07, 08, 09 are linked to Salary #1 and locked.
     * 2. 10, 11, 12, 13 remain NULL and selectable.
     * 3. Salary #2 calculation for 07-13 excludes 07-09.
     * 4. Salary #2 calculation includes 10-12 (13 is Sunday weekly off).
     * 5. No double payment occurs.
     */
    public function test_exact_preexisting_attendance_scenario_07_13_exist_and_salary1_pays_only_07_09(): void
    {
        // Active assignment covering the full week: 07/09/2026 -> 13/09/2026
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-13',
            'status' => 'active',
            'assigned_by' => $this->admin->id,
        ]);

        $eid = $this->admin->id;

        // 1. Create ALL 7 attendance records BEFORE Salary #1 is created:
        // 2026-09-07 is Monday, 2026-09-13 is Sunday
        $att07 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-07', 'status' => 'present']);
        $att08 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-08', 'status' => 'present']);
        $att09 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-09', 'status' => 'present']);
        $att10 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-10', 'status' => 'present']);
        $att11 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-11', 'status' => 'present']);
        $att12 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-12', 'status' => 'present']);
        $att13 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-13', 'status' => 'present']);

        // Assert all 7 initially have labour_salary_id = NULL
        $this->assertNull($att07->labour_salary_id);
        $this->assertNull($att08->labour_salary_id);
        $this->assertNull($att09->labour_salary_id);
        $this->assertNull($att10->labour_salary_id);
        $this->assertNull($att11->labour_salary_id);
        $this->assertNull($att12->labour_salary_id);
        $this->assertNull($att13->labour_salary_id);

        // 2. Create Salary #1 paying ONLY 07, 08, 09 via controller store()
        $response1 = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-13',
            'salary_amount' => 1500.00,
            'paid_amount' => 1500.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-09',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => [$att07->id, $att08->id, $att09->id],
        ]);
        $response1->assertSessionHasNoErrors();

        $salary1 = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary1);
        $this->assertEquals('paid', $salary1->status);

        // 3. ASSERT: 07, 08, 09 are linked to Salary #1
        $att07->refresh();
        $att08->refresh();
        $att09->refresh();
        $this->assertEquals($salary1->id, $att07->labour_salary_id);
        $this->assertEquals($salary1->id, $att08->labour_salary_id);
        $this->assertEquals($salary1->id, $att09->labour_salary_id);

        // 4. ASSERT: 10, 11, 12, 13 MUST remain NULL
        $att10->refresh();
        $att11->refresh();
        $att12->refresh();
        $att13->refresh();
        $this->assertNull($att10->labour_salary_id);
        $this->assertNull($att11->labour_salary_id);
        $this->assertNull($att12->labour_salary_id);
        $this->assertNull($att13->labour_salary_id);

        // 5. ASSERT UI / LOCKING BEHAVIOR:
        // 07, 08, 09 are locked (Salary Paid)
        $res07 = $this->get(route('labour-attendances.index', ['project_id' => $this->project->id, 'date' => '2026-09-07']));
        $res07->assertOk();
        $res07->assertSee('Salary Paid');
        $res07->assertSee('Locked');

        $res08 = $this->get(route('labour-attendances.index', ['project_id' => $this->project->id, 'date' => '2026-09-08']));
        $res08->assertOk();
        $res08->assertSee('Salary Paid');
        $res08->assertSee('Locked');

        $res09 = $this->get(route('labour-attendances.index', ['project_id' => $this->project->id, 'date' => '2026-09-09']));
        $res09->assertOk();
        $res09->assertSee('Salary Paid');
        $res09->assertSee('Locked');

        // 10, 11, 12, 13 are NOT locked as Salary Paid; attendance is editable/selectable
        $res10 = $this->get(route('labour-attendances.index', ['project_id' => $this->project->id, 'date' => '2026-09-10']));
        $res10->assertOk();
        $res10->assertDontSee('Salary Paid');

        $res11 = $this->get(route('labour-attendances.index', ['project_id' => $this->project->id, 'date' => '2026-09-11']));
        $res11->assertOk();
        $res11->assertDontSee('Salary Paid');

        $res12 = $this->get(route('labour-attendances.index', ['project_id' => $this->project->id, 'date' => '2026-09-12']));
        $res12->assertOk();
        $res12->assertDontSee('Salary Paid');

        // 6. CALCULATE SALARY #2 FOR THE REMAINING DATES:
        $summary2 = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-13');

        // 07, 08, 09 are excluded (already paid)
        // 10, 11, 12 are included (unpaid) -> 3 days
        // 13 is Sunday -> excluded
        $this->assertEquals(3.0, $summary2['payable_days']);
        $this->assertEquals(3, $summary2['present_days']);
        $this->assertEquals(1500.00, $summary2['calculated_salary']);
        $this->assertEquals([$att10->id, $att11->id, $att12->id], $summary2['attendance_ids']);

        // 7. PAY SALARY #2 USING THE DERIVED ATTENDANCE IDS
        $response2 = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-13',
            'salary_amount' => 1500.00,
            'paid_amount' => 1500.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-13',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => $summary2['attendance_ids'],
        ]);
        $response2->assertSessionHasNoErrors();

        $salary2 = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertNotNull($salary2);
        $this->assertNotEquals($salary1->id, $salary2->id);

        $att10->refresh();
        $att11->refresh();
        $att12->refresh();
        $att13->refresh();
        $this->assertEquals($salary2->id, $att10->labour_salary_id);
        $this->assertEquals($salary2->id, $att11->labour_salary_id);
        $this->assertEquals($salary2->id, $att12->labour_salary_id);
        $this->assertNull($att13->labour_salary_id); // Sunday remains unlinked

        // 8. PROVE NO DOUBLE PAYMENT
        $summary3 = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-13');
        $this->assertEquals(0.0, $summary3['payable_days']);
        $this->assertEquals(0.00, $summary3['calculated_salary']);
        $this->assertEmpty($summary3['attendance_ids']);

        $totalPaid = LabourSalary::where('labour_id', $this->labour->id)->where('status', 'paid')->sum('paid_amount');
        $this->assertEquals(3000.00, (float) $totalPaid);
    }

    /**
     * Anti-tampering: Attempting to link an attendance record from another labour fails safely.
     */
    public function test_tampered_attendance_id_belonging_to_another_labour_is_rejected(): void
    {
        $otherLabour = Labour::create([
            'name' => 'Other Worker',
            'phone_number' => '9999999999',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
            'status' => 'active',
        ]);

        $otherAtt = LabourAttendance::create([
            'labour_id' => $otherLabour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-13',
            'salary_amount' => 500.00,
            'paid_amount' => 500.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);

        $salary->linkAttendances([$otherAtt->id]);
    }

    /**
     * Anti-duplicate: Attempting to link an attendance record that is already paid to another salary fails safely.
     */
    public function test_already_paid_attendance_cannot_be_linked_to_another_salary(): void
    {
        $att = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);

        $salary1 = $this->createPaidSalary('2026-09-07', '2026-09-09', 500.00, 'paid', [$att->id]);
        $att->refresh();
        $this->assertEquals($salary1->id, $att->labour_salary_id);

        $salary2 = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-13',
            'salary_amount' => 500.00,
            'paid_amount' => 500.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $salary2->linkAttendances([$att->id]);
    }

    /**
     * Sunday attendance cannot be linked to salary payment.
     */
    public function test_sunday_attendance_cannot_be_linked_to_salary(): void
    {
        // 2026-09-13 is Sunday
        $sundayAtt = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-13',
            'status' => 'present',
        ]);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-13',
            'salary_amount' => 500.00,
            'paid_amount' => 500.00,
            'payment_date' => '2026-09-13',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $salary->linkAttendances([$sundayAtt->id]);
    }

    /**
     * Deleting a salary releases only its linked attendances (sets labour_salary_id = NULL).
     * Does NOT touch attendances linked to other salaries or unlinked attendances.
     */
    public function test_salary_deletion_releases_only_linked_attendances(): void
    {
        $att1 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-07', 'status' => 'present']);
        $att2 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-08', 'status' => 'present']);
        $att3 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-09', 'status' => 'present']);

        $salary1 = $this->createPaidSalary('2026-09-07', '2026-09-07', 500.00, 'paid', [$att1->id]);
        $salary2 = $this->createPaidSalary('2026-09-08', '2026-09-08', 500.00, 'paid', [$att2->id]);

        $att1->refresh();
        $att2->refresh();
        $att3->refresh();
        $this->assertEquals($salary1->id, $att1->labour_salary_id);
        $this->assertEquals($salary2->id, $att2->labour_salary_id);
        $this->assertNull($att3->labour_salary_id);

        // Delete salary1
        $salary1->delete();

        $att1->refresh();
        $att2->refresh();
        $att3->refresh();

        // att1 is now released
        $this->assertNull($att1->labour_salary_id);
        // att2 remains linked to salary2
        $this->assertEquals($salary2->id, $att2->labour_salary_id);
        // att3 remains unlinked
        $this->assertNull($att3->labour_salary_id);
    }

    /**
     * Editing/updating a salary does NOT attach unrelated unpaid attendances inside the period.
     */
    public function test_salary_update_does_not_attach_unrelated_attendance(): void
    {
        $att1 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-07', 'status' => 'present']);
        $salary = $this->createPaidSalary('2026-09-07', '2026-09-13', 500.00, 'paid', [$att1->id]);

        // Create new attendance on 2026-09-08
        $att2 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-08', 'status' => 'present']);
        $this->assertNull($att2->labour_salary_id);

        // Update salary via controller
        $this->put(route('labour-salaries.update', $salary), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-13',
            'salary_amount' => 500.00,
            'paid_amount' => 500.00,
            'advance_paid' => 0,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Updated notes',
        ])->assertSessionHasNoErrors();

        $att1->refresh();
        $att2->refresh();

        $this->assertEquals($salary->id, $att1->labour_salary_id);
        // att2 MUST STILL BE NULL!
        $this->assertNull($att2->labour_salary_id);
    }

    /**
     * Half Day attendance calculation & Advance Paid toggle ON/OFF with explicit linking.
     */
    public function test_half_day_attendance_and_advance_toggle_with_explicit_linking(): void
    {
        $attHalf = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'half_day',
        ]);

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-07');
        $this->assertEquals(0.5, $summary['payable_days']);
        $this->assertEquals(250.00, $summary['calculated_salary']);
        $this->assertEquals([$attHalf->id], $summary['attendance_ids']);

        // Test with advance paid ON
        $this->labour->update(['advance_amt' => 100.00]);

        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 250.00,
            'advance_paid' => 1,
            'advance_adjusted' => 100.00,
            'paid_amount' => 150.00, // net payable 250 - 100 = 150
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'attendance_ids' => $summary['attendance_ids'],
        ]);
        $response->assertSessionHasNoErrors();

        $attHalf->refresh();
        $salary = LabourSalary::where('labour_id', $this->labour->id)->latest('id')->first();
        $this->assertEquals($salary->id, $attHalf->labour_salary_id);
        $this->assertEquals(150.00, (float) $salary->paid_amount);
        $this->assertEquals(100.00, (float) $salary->advance_adjusted);
    }
}
