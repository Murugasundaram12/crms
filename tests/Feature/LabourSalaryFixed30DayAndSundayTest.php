<?php

namespace Tests\Feature;

use App\Http\Controllers\LabourAttendanceController;
use App\Models\Client;
use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\LabourAttendance;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourSalaryFixed30DayAndSundayTest extends TestCase
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
            'email' => 'audit02_test_' . uniqid() . '@example.com',
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

        $client = Client::create([
            'name' => 'Audit 02 Client ' . uniqid(),
        ]);

        $this->project = Project::create([
            'client_id' => $client->id,
            'name' => 'Project Audit ' . uniqid(),
            'project_code' => 'PRJ-AUD-' . strtoupper(uniqid()),
            'type' => 'general',
            'status' => 'active',
        ]);

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['name' => 'Cash'],
            ['code' => 'CASH', 'type' => 'Cash', 'active_status' => 1, 'sort_order' => 1]
        );

        $monthlyRole = LabourRole::create([
            'name' => 'Audit 02 Monthly Role ' . uniqid(),
            'salary_type' => 'monthly',
            'salary' => 26000.00,
        ]);

        $this->labour = Labour::create([
            'name' => 'Audit Labour ' . uniqid(),
            'phone' => '9888777661',
            'phone_number' => '9888777661',
            'labour_role_id' => $monthlyRole->id,
            'salary' => 26000.00,
            'advance_amt' => 0.00,
        ]);

        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-31',
            'status' => 'active',
        ]);
    }

    // 1. Monthly labour with fixed-30-day rate: one eligible present day produces monthly_salary / 30
    public function test_01_monthly_labour_one_eligible_present_day_produces_monthly_salary_over_30(): void
    {
        LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-07');

        $this->assertEquals(1.0, $summary['payable_days']);
        $this->assertEquals(866.67, $summary['daily_rate']);
        // 26000 / 30 = 866.67
        $this->assertEquals(866.67, $summary['calculated_salary']);
    }

    // 2. Six eligible present days produce 6 * monthly_salary / 30, not the full monthly salary
    public function test_02_monthly_labour_six_eligible_present_days_produces_six_times_rate_not_full_monthly(): void
    {
        foreach (['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10', '2026-09-11', '2026-09-12'] as $date) {
            LabourAttendance::create([
                'labour_id' => $this->labour->id,
                'employee_id' => $this->admin->id,
                'attendance_date' => $date,
                'status' => 'present',
            ]);
        }

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-12');

        $this->assertEquals(6.0, $summary['payable_days']);
        $this->assertEquals(866.67, $summary['daily_rate']);
        // 6 * (26000 / 30) = 5200.00, NOT 26000.00
        $this->assertEquals(5200.00, $summary['calculated_salary']);
    }

    // 3. Half-days and absences produce the correct payable-day total
    public function test_03_half_days_and_absences_produce_correct_payable_days_total(): void
    {
        // 3 present, 2 half-day, 1 absent = 4.0 payable days
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-07', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-08', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-09', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-10', 'status' => 'half_day']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-11', 'status' => 'half_day']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-12', 'status' => 'absent']);

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-12');

        $this->assertEquals(3, $summary['present_days']);
        $this->assertEquals(2, $summary['half_days']);
        $this->assertEquals(1, $summary['absent_days']);
        $this->assertEquals(4.0, $summary['payable_days']);
        // 4 * (26000 / 30) = 3466.67
        $this->assertEquals(3466.67, $summary['calculated_salary']);
    }

    // 4. Attendance spanning two months uses fixed-30-day denominator
    public function test_04_cross_month_range_uses_applicable_fixed_30_day_denominator_across_months(): void
    {
        $this->labour->update(['salary' => 30000.00]);

        // 3 days in Sept, 3 days in Oct
        $dates = ['2026-09-28', '2026-09-29', '2026-09-30', '2026-10-01', '2026-10-02', '2026-10-03'];
        foreach ($dates as $date) {
            LabourAttendance::create([
                'labour_id' => $this->labour->id,
                'employee_id' => $this->admin->id,
                'attendance_date' => $date,
                'status' => 'present',
            ]);
        }

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour->fresh(), '2026-09-28', '2026-10-03');

        $this->assertEquals(6.0, $summary['payable_days']);
        $this->assertEquals(1000.00, $summary['daily_rate']);
        // 6 * (30000 / 30) = 6000.00
        $this->assertEquals(6000.00, $summary['calculated_salary']);
    }

    // 5. Full-month attendance preserves expected monthly amount when all 30 days are eligible and payable
    public function test_05_full_month_preserves_expected_monthly_amount_when_all_30_days_payable(): void
    {
        for ($day = 1; $day <= 30; $day++) {
            $date = sprintf('2026-09-%02d', $day);
            LabourAttendance::create([
                'labour_id' => $this->labour->id,
                'employee_id' => $this->admin->id,
                'attendance_date' => $date,
                'status' => 'present',
            ]);
        }

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-01', '2026-09-30');

        $this->assertEquals(30.0, $summary['payable_days']);
        // 30 * (26000 / 30) = 26000.00
        $this->assertEquals(26000.00, $summary['calculated_salary']);
    }

    // 6. Sunday attendance can be created and saved through single-entry and bulk-entry flows
    public function test_06_sunday_attendance_can_be_created_via_single_and_bulk_store(): void
    {
        // 2026-09-06 is Sunday
        $resSingle = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
            'notes' => 'Sunday single store',
        ]);
        $resSingle->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);

        // 2026-09-13 is Sunday
        $resBulk = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-13',
            'attendances' => [
                [
                    'labour_id' => $this->labour->id,
                    'status' => 'half_day',
                    'notes' => 'Sunday bulk store',
                ],
            ],
        ]);
        $resBulk->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13',
            'status' => 'half_day',
        ]);
    }

    // 7. Sunday attendance contributes to salary when present or half-day and otherwise eligible
    public function test_07_sunday_attendance_contributes_to_salary_when_present_or_half_day(): void
    {
        // 2026-09-06 is Sunday (present = 1.0 day), 2026-09-13 is Sunday (half_day = 0.5 day)
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-06', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-13', 'status' => 'half_day']);

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-01', '2026-09-15');

        $this->assertEquals(1.5, $summary['payable_days']);
        // 1.5 * (26000 / 30) = 1300.00
        $this->assertEquals(1300.00, $summary['calculated_salary']);
    }

    // 8. Sunday attendance can be linked to a salary record
    public function test_08_sunday_attendance_can_be_linked_to_salary_record(): void
    {
        $attSun = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-06', // Sunday
            'status' => 'present',
        ]);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-10',
            'salary_amount' => 866.67,
            'paid_amount' => 866.67,
            'remaining_amount' => 0.00,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-10',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);

        $salary->linkAttendances([$attSun->id]);

        $attSun->refresh();
        $this->assertEquals($salary->id, $attSun->labour_salary_id);
    }

    // 9. Previously fully paid attendance is excluded from new salary calculations
    public function test_09_previously_fully_paid_attendance_is_excluded_from_new_salary_calculations(): void
    {
        $att1 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-07', 'status' => 'present']);
        $att2 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-08', 'status' => 'present']);
        $att3 = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-09', 'status' => 'present']);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-08',
            'salary_amount' => 1733.34,
            'paid_amount' => 1733.34,
            'remaining_amount' => 0.00,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);
        $salary->linkAttendances([$att1->id, $att2->id]);

        // Calculate for 07 to 09: only 09 is unpaid
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-09');

        $this->assertEquals(1.0, $summary['payable_days']);
        $this->assertEquals(1, $summary['present_days']);
        $this->assertEquals([$att3->id], $summary['attendance_ids']);
    }

    // 10. Partial salary records retain established partial-payment workflow
    public function test_10_partial_salary_records_retain_established_partial_payment_workflow(): void
    {
        $att = LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-07', 'status' => 'present']);

        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 866.67,
            'paid_amount' => 400.00,
            'remaining_amount' => 466.67,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'partial',
        ]);
        $salary->linkAttendances([$att->id]);

        // Partial salary does NOT exclude attendance, allowing existing salary to be completed
        $this->assertFalse(LabourAttendanceController::isAttendancePaid($att));

        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-07', '2026-09-07');
        $this->assertEquals(1.0, $summary['payable_days']);
    }

    // 11. Daily and weekly salary calculations remain unchanged
    public function test_11_daily_and_weekly_salary_calculations_remain_unchanged(): void
    {
        // Daily worker
        $dailyRole = LabourRole::create([
            'name' => 'Daily Test Role ' . uniqid(),
            'salary_type' => 'daily',
            'salary' => 700.00,
        ]);
        $this->labour->update(['labour_role_id' => $dailyRole->id, 'salary' => 700.00]);

        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-07', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $this->admin->id, 'attendance_date' => '2026-09-08', 'status' => 'half_day']);

        $dailySummary = LabourAttendanceController::calculatePeriodSummary($this->labour->fresh('labourRole'), '2026-09-07', '2026-09-08');
        $this->assertEquals(1.5, $dailySummary['payable_days']);
        $this->assertEquals(700.00, $dailySummary['daily_rate']);
        $this->assertEquals(1050.00, $dailySummary['calculated_salary']); // 1.5 * 700

        // Weekly worker
        $weeklyRole = LabourRole::create([
            'name' => 'Weekly Test Role ' . uniqid(),
            'salary_type' => 'weekly',
            'salary' => 4800.00,
        ]);
        $this->labour->update(['labour_role_id' => $weeklyRole->id, 'salary' => 4800.00]);

        $weeklySummary = LabourAttendanceController::calculatePeriodSummary($this->labour->fresh('labourRole'), '2026-09-07', '2026-09-08');
        $this->assertEquals(1.5, $weeklySummary['payable_days']);
        $this->assertEquals(800.00, $weeklySummary['daily_rate']); // 4800 / 6 = 800
        $this->assertEquals(1200.00, $weeklySummary['calculated_salary']); // 1.5 * 800
    }

    // 12. Duplicate payment/linking remains blocked
    public function test_12_duplicate_payment_and_linking_remains_blocked(): void
    {
        $att = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);

        $salary1 = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 866.67,
            'paid_amount' => 866.67,
            'remaining_amount' => 0.00,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);
        $salary1->linkAttendances([$att->id]);

        $salary2 = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-07',
            'salary_amount' => 866.67,
            'paid_amount' => 866.67,
            'remaining_amount' => 0.00,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $salary2->linkAttendances([$att->id]);
    }

    // 13. Salary calculation handles empty attendance range without errors or fabricated salary
    public function test_13_salary_calculation_handles_empty_attendance_range_without_errors(): void
    {
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-20', '2026-09-25');

        $this->assertEquals(0, $summary['present_days']);
        $this->assertEquals(0, $summary['half_days']);
        $this->assertEquals(0, $summary['absent_days']);
        $this->assertEquals(0.0, $summary['payable_days']);
        $this->assertEquals(0.00, $summary['calculated_salary']);
        $this->assertEmpty($summary['attendance_ids']);
    }
}
