<?php

namespace Tests\Feature;

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
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourAttendanceAndSalaryModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Labour $labour;
    protected Project $project;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // Required to match APP_URL=https://housefix360.com/crms so routes resolve correctly
        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->admin = User::factory()->create([
            'email' => 'labour_test_' . uniqid() . '@example.com',
        ]);

        // Use DB update to set role/wallet (avoids factory fillable issues)
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
            'name' => 'Kannan Test ' . uniqid(),
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
            'name' => 'Site Alpha ' . uniqid(),
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

    // 1. Assignment can be created
    public function test_assignment_can_be_created(): void
    {
        $response = $this->post(route('labour_assignments.store'), [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
            'notes' => 'Site assignment',
        ]);

        $response->assertRedirect(route('labour_assignments.index'));
        $this->assertDatabaseHas('labour_assignments', [
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'status' => 'active',
        ]);
    }

    // 2. Attendance allowed on start date & 3. Attendance allowed on end date
    public function test_attendance_allowed_on_start_and_end_date(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01', // Tuesday
            'end_date' => '2026-09-05',   // Saturday
            'status' => 'active',
        ]);

        // Start date attendance
        $resStart = $this->post(route('labour-attendances.store'), [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);
        $resStart->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);

        // End date attendance
        $resEnd = $this->post(route('labour-attendances.store'), [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-05',
            'status' => 'present',
        ]);
        $resEnd->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-05',
            'status' => 'present',
        ]);
    }

    // 4. Attendance rejected before start date & 5. Attendance rejected after end date
    public function test_attendance_rejected_outside_assignment_date_range(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-02', // Wednesday
            'end_date' => '2026-09-05',   // Saturday
            'status' => 'active',
        ]);

        // Before start date (Sept 1 - Tuesday)
        $resBefore = $this->post(route('labour-attendances.store'), [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);
        $resBefore->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
        ]);

        // After end date (Sept 7 - Monday)
        $resAfter = $this->post(route('labour-attendances.store'), [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);
        $resAfter->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-07',
        ]);
    }

    // 6. Mid-week assignment works
    public function test_mid_week_assignment(): void
    {
        // Assigned starting Wednesday Sept 2
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-05',
            'status' => 'active',
        ]);

        // Sept 2 (Wed) works
        $resWed = $this->post(route('labour-attendances.store'), [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
        ]);
    }

    // 8. Sunday attendance is allowed and can be saved
    public function test_sunday_attendance_allowed_and_can_be_saved(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        // Sept 6, 2026 is Sunday
        $response = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);
    }

    // 9. Daily salary calculation
    public function test_daily_salary_calculation(): void
    {
        // 5 payable days @ 500 = 2500
        $eid = $this->admin->id;
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-01', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-02', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-03', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-04', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-05', 'status' => 'present']);

        $summary = \App\Http\Controllers\LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-01', '2026-09-05');
        $this->assertEquals(5.0, $summary['payable_days']);
        $this->assertEquals(2500.00, $summary['calculated_salary']);
    }

    // 10. Weekly salary calculation using /6
    public function test_weekly_salary_calculation(): void
    {
        $weeklyRole = LabourRole::create([
            'name' => 'Weekly Fitter Test ' . uniqid(),
            'salary_type' => 'weekly',
            'salary' => 3000.00, // 3000 / 6 = 500/day
        ]);
        $this->labour->update(['labour_role_id' => $weeklyRole->id]);

        $eid = $this->admin->id;
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-01', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-02', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-03', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-04', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-05', 'status' => 'present']);

        $summary = \App\Http\Controllers\LabourAttendanceController::calculatePeriodSummary($this->labour->fresh('labourRole'), '2026-09-01', '2026-09-05');
        $this->assertEquals(5.0, $summary['payable_days']);
        $this->assertEquals(2500.00, $summary['calculated_salary']);
    }

    // 12. Half-day calculation & 13. Absent = zero payable
    public function test_half_day_and_absent_calculation(): void
    {
        // 1 Present (1), 1 Half Day (0.5), 1 Absent (0) = 1.5 days @ 500 = 750
        $eid = $this->admin->id;
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-01', 'status' => 'present']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-02', 'status' => 'half_day']);
        LabourAttendance::create(['labour_id' => $this->labour->id, 'employee_id' => $eid, 'attendance_date' => '2026-09-03', 'status' => 'absent']);

        $summary = \App\Http\Controllers\LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-01', '2026-09-03');
        $this->assertEquals(1.5, $summary['payable_days']);
        $this->assertEquals(750.00, $summary['calculated_salary']);
    }

    // 14. Advance adjustment calculation, 18. Payment method required, 21. Wallet debit paid amount only, 23. No double debit, 24. Advance balance reduces
    public function test_salary_payment_with_advance_adjustment_and_wallet_movements(): void
    {
        // Set initial advance on Labour = 1000
        $this->labour->update(['advance_amt' => 1000.00]);
        $initialPayerWallet = (float) $this->admin->wallet;

        // Earned salary = 2500, Advance adjustment = 500, Net payable = 2000
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-05',
            'salary_amount' => 2500.00,
            'advance_adjusted' => 500.00,
            'paid_amount' => 2000.00,
            'payment_date' => '2026-09-05',
            'payment_method_id' => $this->paymentMethod->id,
            'notes' => 'Salary payment for week 1',
        ]);

        $response->assertRedirect(route('labour-salaries.index'));

        // Check Labour advance reduced by 500 -> remaining 500
        $this->assertEquals(500.00, (float) $this->labour->fresh()->advance_amt);

        // Check Payer wallet debited by 2000 ONLY (not 2500)
        $this->assertEquals($initialPayerWallet - 2000.00, (float) $this->admin->fresh()->wallet);

        // Check AdvanceHistory settlement record created
        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $this->labour->id,
            'amount' => 500.00,
            'entry_type' => 'settle',
        ]);

        // Check Wallet ledger transaction created with payment_method_id
        $this->assertDatabaseHas('wallet', [
            'user_id' => $this->admin->id,
            'amount' => 2000,
            'payment_method_id' => $this->paymentMethod->id,
            'source_type' => 'labour_salary',
        ]);
    }

    // 18. Payment method required validation error
    public function test_salary_payment_requires_payment_method(): void
    {
        $response = $this->post(route('labour-salaries.store'), [
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-05',
            'salary_amount' => 2500.00,
            'paid_amount' => 2500.00,
            'payment_date' => '2026-09-05',
            'payment_method_id' => null, // Missing payment method
        ]);

        $response->assertSessionHasErrors('payment_method_id');
    }

    // 27. Transaction rollback occurs when payment operation fails
    public function test_transaction_rollback_on_excess_advance_adjustment(): void
    {
        $this->labour->update(['advance_amt' => 200.00]);
        $initialPayerWallet = (float) $this->admin->wallet;

        // Trying to adjust 500 advance when only 200 available -> should throw and rollback
        try {
            $this->post(route('labour-salaries.store'), [
                'labour_id' => $this->labour->id,
                'salary_period_start' => '2026-09-01',
                'salary_period_end' => '2026-09-05',
                'salary_amount' => 2500.00,
                'advance_adjusted' => 500.00, // Exceeds 200
                'paid_amount' => 2000.00,
                'payment_date' => '2026-09-05',
                'payment_method_id' => $this->paymentMethod->id,
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        // Payer wallet and Labour advance must remain untouched
        $this->assertEquals($initialPayerWallet, (float) $this->admin->fresh()->wallet);
        $this->assertEquals(200.00, (float) $this->labour->fresh()->advance_amt);
    }

    // --- SITE-WISE ATTENDANCE FEATURE TESTS ---

    // 1. Site selection shows only assigned labour & 2. Labour assigned to another site is excluded
    public function test_site_selection_shows_only_assigned_labour_and_excludes_others(): void
    {
        $siteB = Project::create([
            'client_id' => $this->project->client_id,
            'name' => 'Site Beta ' . uniqid(),
            'project_code' => 'PRJ-' . strtoupper(uniqid()),
            'type' => 'general',
            'status' => 'active',
        ]);

        $labourB = Labour::create([
            'name' => 'Murugan Test ' . uniqid(),
            'phone' => '9876543211',
            'phone_number' => '9876543211',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        // Labour A assigned to Site Alpha
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // Labour B assigned to Site Beta
        LabourAssignment::create([
            'labour_id' => $labourB->id,
            'project_id' => $siteB->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // When Site Alpha selected on 2026-09-02: shows Labour A, excludes Labour B
        $responseAlpha = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-02',
        ]));
        $responseAlpha->assertOk();
        $responseAlpha->assertSee($this->labour->name);
        $responseAlpha->assertDontSee($labourB->name);

        // When Site Beta selected on 2026-09-02: shows Labour B, excludes Labour A
        $responseBeta = $this->get(route('labour-attendances.index', [
            'project_id' => $siteB->id,
            'date' => '2026-09-02',
        ]));
        $responseBeta->assertOk();
        $responseBeta->assertSee($labourB->name);
        $responseBeta->assertDontSee($this->labour->name);
    }

    // 3. Before start excluded, 4. After end excluded, 5. Start included, 6. End included, 7. Mid-week in UI
    public function test_ui_date_filtering_for_assigned_labour(): void
    {
        // Assigned Wednesday 2026-09-02 to Saturday 2026-09-05
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-02',
            'end_date' => '2026-09-05',
            'status' => 'active',
        ]);

        // Before start date (Tuesday 2026-09-01) -> excluded, shows empty state
        $resBefore = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-01',
        ]));
        $resBefore->assertOk();
        $resBefore->assertSee('No labour assigned to this site for the selected date.');
        $resBefore->assertDontSee($this->labour->name);

        // On start date (Wednesday 2026-09-02) -> included
        $resStart = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-02',
        ]));
        $resStart->assertOk();
        $resStart->assertSee($this->labour->name);

        // On end date (Saturday 2026-09-05) -> included
        $resEnd = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-05',
        ]));
        $resEnd->assertOk();
        $resEnd->assertSee($this->labour->name);

        // After end date (Monday 2026-09-07) -> excluded, shows empty state
        $resAfter = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-07',
        ]));
        $resAfter->assertOk();
        $resAfter->assertSee('No labour assigned to this site for the selected date.');
        $resAfter->assertDontSee($this->labour->name);
    }

    // 8. Sunday UI shows attendance entry without weekly off message & 9. Sunday Present, Half Day, Absent can be saved
    public function test_sunday_ui_shows_attendance_entry_without_weekly_off_message(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        // 2026-09-06 is Sunday
        $resUi = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-06',
        ]));
        $resUi->assertOk();
        $resUi->assertDontSee('Sunday is Weekly Off — Labour attendance is not available.');
        $resUi->assertDontSee('Sunday Weekly Off:');
        $resUi->assertSee('Save Attendance Records');
        $resUi->assertSee($this->labour->name);
    }

    public function test_sunday_present_half_day_and_absent_can_be_saved(): void
    {
        $labour2 = Labour::create([
            'name' => 'Sunday Labour 2 ' . uniqid(),
            'phone' => '9876543222',
            'phone_number' => '9876543222',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        $labour3 = Labour::create([
            'name' => 'Sunday Labour 3 ' . uniqid(),
            'phone' => '9876543223',
            'phone_number' => '9876543223',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        LabourAssignment::create([
            'labour_id' => $labour2->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        LabourAssignment::create([
            'labour_id' => $labour3->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        // 2026-09-06 is Sunday: Save present via single store
        $resPresent = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
            'notes' => 'Sunday work',
        ]);
        $resPresent->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);

        // Bulk store: save half_day and absent on Sunday
        $resBulk = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-06',
            'attendances' => [
                ['labour_id' => $labour2->id, 'status' => 'half_day', 'notes' => 'Half day Sunday'],
                ['labour_id' => $labour3->id, 'status' => 'absent', 'notes' => 'Absent Sunday'],
            ],
        ]);
        $resBulk->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $labour2->id,
            'attendance_date' => '2026-09-06',
            'status' => 'half_day',
        ]);
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $labour3->id,
            'attendance_date' => '2026-09-06',
            'status' => 'absent',
        ]);
    }

    public function test_sunday_outside_assignment_range_is_still_rejected(): void
    {
        // Assigned Tuesday Sept 8 to Saturday Sept 12
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-12',
            'status' => 'active',
        ]);

        // Sunday Sept 6 is before start date -> rejected
        $resBefore = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);
        $resBefore->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
        ]);

        // Sunday Sept 13 is after end date -> rejected
        $resAfter = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13',
            'status' => 'present',
        ]);
        $resAfter->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13',
        ]);
    }

    public function test_sunday_assignment_start_and_end_dates_are_inclusive(): void
    {
        // Assigned from Sunday Sept 6 to Sunday Sept 13
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-06', // Sunday
            'end_date' => '2026-09-13',   // Sunday
            'status' => 'active',
        ]);

        // Start date (Sunday Sept 6) -> allowed
        $resStart = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);
        $resStart->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
            'status' => 'present',
        ]);

        // End date (Sunday Sept 13) -> allowed
        $resEnd = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13',
            'status' => 'present',
        ]);
        $resEnd->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13',
            'status' => 'present',
        ]);
    }

    // 10. Manually submitting a labour from another site is rejected (server-side protection)
    public function test_manually_submitting_labour_from_another_site_is_rejected(): void
    {
        $siteB = Project::create([
            'client_id' => $this->project->client_id,
            'name' => 'Site Beta ' . uniqid(),
            'project_code' => 'PRJ-' . strtoupper(uniqid()),
            'type' => 'general',
            'status' => 'active',
        ]);

        $labourB = Labour::create([
            'name' => 'Ramesh Other Site ' . uniqid(),
            'phone' => '9876543212',
            'phone_number' => '9876543212',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);

        // Labour B is assigned to Site Beta ONLY
        LabourAssignment::create([
            'labour_id' => $labourB->id,
            'project_id' => $siteB->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // Manually send project_id = Site Alpha, but labour_id = Labour B
        $resSingle = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $labourB->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);
        $resSingle->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $labourB->id,
            'attendance_date' => '2026-09-02',
        ]);

        // Manually send via bulk-store
        $resBulk = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-02',
            'attendances' => [
                ['labour_id' => $labourB->id, 'project_id' => $this->project->id, 'status' => 'present'],
            ],
        ]);
        $resBulk->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $labourB->id,
            'attendance_date' => '2026-09-02',
        ]);
    }

    // 11. Existing attendance duplicate protection still works
    public function test_duplicate_attendance_protection_still_works(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        $res1 = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);
        $res1->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
        ]);

        // Second store attempt for same date
        $res2 = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
            'status' => 'absent',
        ]);
        $res2->assertSessionHas('error');
        $this->assertEquals(1, LabourAttendance::where('labour_id', $this->labour->id)->where('attendance_date', '2026-09-02')->count());
    }

    // 12. Existing paid salary attendance lock still works
    public function test_paid_salary_attendance_lock_still_works(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        // Create attendance record
        $att = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
        ]);

        // Create paid salary for period
        LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-05',
            'salary_amount' => 2500,
            'paid_amount' => 2500,
            'status' => 'paid',
            'payment_date' => '2026-09-05',
        ]);

        // Cannot add attendance for date in paid period
        $resAdd = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-03',
            'status' => 'present',
        ]);
        $resAdd->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-03',
        ]);

        // Cannot delete attendance in paid period
        $resDel = $this->delete(route('labour-attendances.destroy', $att));
        $resDel->assertSessionHas('error');
        $this->assertDatabaseHas('labour_attendances', ['id' => $att->id]);
    }

    // 13. Existing attendance history still works with site/project display
    public function test_attendance_history_table_displays_site_project_column(): void
    {
        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'status' => 'active',
        ]);

        LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-02',
            'status' => 'present',
            'notes' => 'Tested on site',
        ]);

        $response = $this->get(route('labour-attendances.index', [
            'date' => '2026-09-02',
        ]));
        $response->assertOk();
        $response->assertSee($this->labour->name);
        $response->assertSee($this->project->name);
        $response->assertSee('Tested on site');
    }
}

