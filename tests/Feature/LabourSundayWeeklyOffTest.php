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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourSundayWeeklyOffTest extends TestCase
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
            'email' => 'sunday_test_' . uniqid() . '@example.com',
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
            ['name' => 'Daily Test Role'],
            ['salary_type' => 'daily', 'salary' => 600.00]
        );

        $this->labour = Labour::create([
            'name' => 'Sunday Labour ' . uniqid(),
            'phone' => '9998887771',
            'phone_number' => '9998887771',
            'labour_role_id' => $dailyRole->id,
            'salary' => 600.00,
            'advance_amt' => 0.00,
        ]);

        $client = Client::create([
            'name' => 'Test Client ' . uniqid(),
        ]);

        $this->project = Project::create([
            'client_id' => $client->id,
            'name' => 'Project Sun ' . uniqid(),
            'project_code' => 'PRJ-SUN-' . strtoupper(uniqid()),
            'type' => 'general',
            'status' => 'active',
        ]);

        $this->paymentMethod = PaymentMethod::firstOrCreate(
            ['name' => 'Cash'],
            ['code' => 'CASH', 'type' => 'Cash', 'active_status' => 1, 'sort_order' => 1]
        );

        LabourAssignment::create([
            'labour_id' => $this->labour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);
    }

    // 1. Sunday attendance cannot be created via single store
    public function test_1_sunday_attendance_cannot_be_created(): void
    {
        $response = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06', // Sunday
            'status' => 'present',
        ]);

        $response->assertSessionHas('error', 'Attendance cannot be recorded on Sunday (Weekly Off).');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
        ]);
    }

    // 2. Sunday attendance cannot be bulk-created
    public function test_2_sunday_attendance_cannot_be_bulk_created(): void
    {
        $response = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-06', // Sunday
            'attendances' => [
                [
                    'labour_id' => $this->labour->id,
                    'status' => 'present',
                    'notes' => 'Bulk Sunday test',
                ],
            ],
        ]);

        $response->assertSessionHas('error', 'Attendance cannot be recorded on Sunday (Weekly Off).');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-06',
        ]);
    }

    // 3. Sunday cannot be marked Present
    public function test_3_sunday_cannot_be_marked_present(): void
    {
        $response = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13', // Sunday
            'status' => 'present',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'attendance_date' => '2026-09-13',
            'status' => 'present',
        ]);
    }

    // 4. Sunday cannot be marked Half Day
    public function test_4_sunday_cannot_be_marked_half_day(): void
    {
        $response = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13', // Sunday
            'status' => 'half_day',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'attendance_date' => '2026-09-13',
            'status' => 'half_day',
        ]);
    }

    // 5. Sunday cannot be marked Absent
    public function test_5_sunday_cannot_be_marked_absent(): void
    {
        $response = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-13', // Sunday
            'status' => 'absent',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'attendance_date' => '2026-09-13',
            'status' => 'absent',
        ]);
    }

    // 6 & 7 & 9. Sunday does not contribute to payable days or salary even if historical Sunday record exists
    public function test_6_and_7_and_9_sunday_historical_records_ignored_in_payable_days_and_salary(): void
    {
        // Manually simulate historical Sunday records that may exist in database
        LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-04', // Friday
            'status' => 'present',
        ]);
        LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-05', // Saturday
            'status' => 'present',
        ]);
        LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-06', // Sunday (historical present!)
            'status' => 'present',
        ]);
        LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07', // Monday
            'status' => 'present',
        ]);

        // Period 2026-09-01 to 2026-09-10
        $summary = LabourAttendanceController::calculatePeriodSummary($this->labour, '2026-09-01', '2026-09-10');

        // Present days must be 3 (Friday, Saturday, Monday). Sunday MUST BE EXCLUDED!
        $this->assertEquals(3, $summary['present_days']);
        $this->assertEquals(3.0, $summary['payable_days']);
        // Daily rate is 600. Calculated salary = 3 * 600 = 1800.00 (NOT 2400.00!)
        $this->assertEquals(1800.00, $summary['calculated_salary']);
    }

    // 8. Sunday does not contribute to monthly working-day divisor
    public function test_8_sunday_does_not_contribute_to_monthly_working_day_divisor(): void
    {
        $monthlyRole = LabourRole::create([
            'name' => 'Monthly Role Test ' . uniqid(),
            'salary_type' => 'monthly',
            'salary' => 26000.00,
        ]);
        $this->labour->update(['labour_role_id' => $monthlyRole->id]);

        // September 2026 has 30 days, 4 Sundays => exactly 26 Mon-Sat working days.
        $summary = LabourAttendanceController::calculateMonthlySummary($this->labour->fresh('labourRole'), '2026-09');

        $this->assertEquals(26, $summary['total_working_days']);
        $this->assertEquals(4, $summary['sunday_count']);
        // Daily rate divisor = 26000 / 26 = 1000.00
        $this->assertEquals(1000.00, $summary['daily_rate']);
    }

    // 10. Monday-Saturday attendance still works
    public function test_10_mon_sat_attendance_still_works(): void
    {
        // 2026-09-01 is Tuesday
        $resSingle = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
            'notes' => 'Tuesday work',
        ]);
        $resSingle->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-01',
            'status' => 'present',
        ]);

        // 2026-09-02 is Wednesday (bulk store)
        $resBulk = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-02',
            'attendances' => [
                [
                    'labour_id' => $this->labour->id,
                    'status' => 'half_day',
                    'notes' => 'Wednesday half day',
                ],
            ],
        ]);
        $resBulk->assertSessionHasNoErrors();
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-02',
            'status' => 'half_day',
        ]);
    }

    // 11 & 12. Mid-week assignment eligibility and outside range filtering
    public function test_11_and_12_midweek_assignment_eligibility_and_filtering(): void
    {
        $newLabour = Labour::create([
            'name' => 'Midweek Labour ' . uniqid(),
            'phone' => '9998887772',
            'phone_number' => '9998887772',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 600.00,
            'advance_amt' => 0.00,
        ]);

        // Assigned Wednesday 2026-09-09 to Friday 2026-09-11
        LabourAssignment::create([
            'labour_id' => $newLabour->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-09',
            'end_date' => '2026-09-11',
            'status' => 'active',
        ]);

        // Monday 2026-09-07 -> not eligible, should not appear in UI
        $resMonUi = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-07',
        ]));
        $resMonUi->assertOk();
        $resMonUi->assertDontSee($newLabour->name);

        // Wednesday 2026-09-09 -> eligible, should appear in UI
        $resWedUi = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-09',
        ]));
        $resWedUi->assertOk();
        $resWedUi->assertSee($newLabour->name);
    }

    // 13 & 14 & 15. Paid salary period locks covered attendance dates, partial does not, unrelated dates not locked
    public function test_13_14_15_salary_paid_locking_behavior(): void
    {
        // Mark attendance on Mon Sep 7 and Tue Sep 8
        $att1 = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);
        $att2 = LabourAttendance::create([
            'labour_id' => $this->labour->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-08',
            'status' => 'present',
        ]);

        // Create a PAID salary record covering Sept 07 to Sept 08
        $salary = LabourSalary::create([
            'labour_id' => $this->labour->id,
            'salary_period_start' => '2026-09-07',
            'salary_period_end' => '2026-09-08',
            'salary_amount' => 1200.00,
            'paid_amount' => 1200.00,
            'remaining_amount' => 0.00,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-08',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);
        $salary->linkAttendances([$att1->id, $att2->id]);

        // Attempting to overwrite attendance on Sep 07 via store is blocked
        $resBlocked = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-07',
            'status' => 'half_day',
        ]);
        $resBlocked->assertSessionHas('error', 'Attendance cannot be modified because salary for this period has already been processed.');

        // Attempting to delete attendance on Sep 07 is blocked
        $att7 = LabourAttendance::where('labour_id', $this->labour->id)->whereDate('attendance_date', '2026-09-07')->first();
        $resDel = $this->delete(route('labour-attendances.destroy', $att7));
        $resDel->assertSessionHas('error', 'Cannot delete attendance for a period where salary has already been paid.');

        // UI on covered date (Sep 08) shows locked state
        $resUiLocked = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-08',
        ]));
        $resUiLocked->assertOk();
        $resUiLocked->assertSee('Salary Paid');
        $resUiLocked->assertSee('Locked');

        // Unrelated date (Sep 09) is NOT locked
        $resUiUnlocked = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-09',
        ]));
        $resUiUnlocked->assertOk();
        $resUiUnlocked->assertDontSee('Salary Paid');
    }

    // Frontend UI on Sunday displays "Sunday - Weekly Off" and hides Save button
    public function test_sunday_frontend_ui_displays_weekly_off(): void
    {
        // 2026-09-06 is Sunday
        $response = $this->get(route('labour-attendances.index', [
            'project_id' => $this->project->id,
            'date' => '2026-09-06',
        ]));

        $response->assertOk();
        $response->assertSee('Sunday - Weekly Off');
        $response->assertDontSee('Save Attendance Records');
    }

    // Past date attendance entry is allowed when assignment is active, date is Mon-Sat, and not paid
    public function test_past_date_attendance_can_be_created(): void
    {
        // Past date: 2026-09-07 (Monday)
        $response = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-07',
            'attendances' => [
                [
                    'labour_id' => $this->labour->id,
                    'status' => 'present',
                    'notes' => 'Past date attendance',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
            'notes' => 'Past date attendance',
        ]);
    }

    // Past date single attendance entry via store() is also allowed
    public function test_past_date_single_attendance_can_be_created(): void
    {
        // Past date: 2026-09-05 (Saturday)
        $response = $this->post(route('labour-attendances.store'), [
            'project_id' => $this->project->id,
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-05',
            'status' => 'half_day',
            'notes' => 'Saturday past attendance',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-05',
            'status' => 'half_day',
        ]);
    }

    // Unlocked labour with no selected status does not cause "attendances.0.status field is required" and skips
    public function test_unlocked_labour_with_no_selected_status_does_not_cause_validation_error_and_skips(): void
    {
        // Payload with status = 'off' (as generated when nothing is selected in UI)
        $response = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-08',
            'attendances' => [
                [
                    'labour_id' => $this->labour->id,
                    'status' => 'off',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-08',
        ]);

        // Payload with status missing/omitted entirely
        $responseMissing = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-08',
            'attendances' => [
                [
                    'labour_id' => $this->labour->id,
                ],
            ],
        ]);

        $responseMissing->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $this->labour->id,
            'attendance_date' => '2026-09-08',
        ]);
    }

    // Current UI scenario: Locked salary-paid row (AACHU) + unselected row (AARUMUGAM) does NOT error
    public function test_locked_salary_paid_row_and_unselected_row_does_not_throw_error(): void
    {
        // Labour 1 (AACHU): already has attendance and paid salary
        $aachu = $this->labour;
        $attAachu = LabourAttendance::create([
            'labour_id' => $aachu->id,
            'employee_id' => $this->admin->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);
        $salAachu = LabourSalary::create([
            'labour_id' => $aachu->id,
            'salary_period_start' => '2026-09-01',
            'salary_period_end' => '2026-09-30',
            'salary_amount' => 4800.00,
            'paid_amount' => 4800.00,
            'remaining_amount' => 0.00,
            'advance_adjusted' => 0.00,
            'payment_date' => '2026-09-07',
            'payment_method_id' => $this->paymentMethod->id,
            'paid_by' => $this->admin->id,
            'status' => 'paid',
        ]);
        $salAachu->linkAttendances([$attAachu->id]);

        // Labour 2 (AARUMUGAM): unlocked, no attendance yet
        $aarumugam = Labour::create([
            'name' => 'AARUMUGAM HELPER ' . uniqid(),
            'phone' => '9998887773',
            'phone_number' => '9998887773',
            'labour_role_id' => $this->labour->labour_role_id,
            'salary' => 500.00,
            'advance_amt' => 0.00,
        ]);
        LabourAssignment::create([
            'labour_id' => $aarumugam->id,
            'project_id' => $this->project->id,
            'employee_id' => $this->admin->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'status' => 'active',
        ]);

        // Scenario: AACHU has status omitted or 'off' (locked), AARUMUGAM has no selection ('off')
        $response = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-07',
            'attendances' => [
                0 => [
                    'labour_id' => $aachu->id,
                    'project_id' => $this->project->id,
                    'status' => 'off',
                ],
                1 => [
                    'labour_id' => $aarumugam->id,
                    'project_id' => $this->project->id,
                    'status' => 'off',
                ],
            ],
        ]);

        // Must NOT throw "The attendances.0.status field is required."
        $response->assertSessionHasNoErrors();
        // AACHU attendance remains intact (present)
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $aachu->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);
        // AARUMUGAM attendance is NOT created
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $aarumugam->id,
            'attendance_date' => '2026-09-07',
        ]);

        // Now test where AARUMUGAM selects 'present':
        $resSave = $this->post(route('labour-attendances.bulk-store'), [
            'project_id' => $this->project->id,
            'attendance_date' => '2026-09-07',
            'attendances' => [
                0 => [
                    'labour_id' => $aachu->id,
                    'project_id' => $this->project->id,
                    // even if status is omitted for locked row
                ],
                1 => [
                    'labour_id' => $aarumugam->id,
                    'project_id' => $this->project->id,
                    'status' => 'present',
                ],
            ],
        ]);

        $resSave->assertSessionHasNoErrors();
        // AARUMUGAM is saved as present
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $aarumugam->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);
        // AACHU is unchanged
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $aachu->id,
            'attendance_date' => '2026-09-07',
            'status' => 'present',
        ]);
    }
}

