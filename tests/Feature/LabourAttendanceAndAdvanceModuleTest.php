<?php

namespace Tests\Feature;

use App\Models\AdvanceHistory;
use App\Models\Labour;
use App\Models\LabourAttendance;
use App\Models\LabourRole;
use App\Models\LabourSalary;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LabourAttendanceAndAdvanceModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected LabourRole $labourRole;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.single_web_session' => false]);
        config(['app.url' => 'http://localhost']);
        url()->forceRootUrl('http://localhost');

        $this->user = User::factory()->create([
            'email' => 'admin_test_' . uniqid() . '@example.com',
        ]);

        $updateData = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $updateData['role'] = 'Super Admin';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'wallet')) {
            $updateData['wallet'] = 50000.00;
        }

        if (! empty($updateData)) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $this->user->id)->update($updateData);
            $this->user = $this->user->fresh();
        }

        $this->labourRole = LabourRole::query()->firstOrCreate(['name' => 'Mason'], ['salary_type' => 'daily', 'salary' => 500.00]);
    }

    protected function createTestLabour(array $attributes = []): Labour
    {
        return Labour::create(array_merge([
            'name' => 'John Workman',
            'phone_number' => '9876543210',
            'phone' => '9876543210',
            'labour_role_id' => $this->labourRole->id,
            'salary' => 26000,
            'advance_amt' => 0,
        ], $attributes));
    }

    /** @test */
    public function it_can_mark_present_absent_and_half_day_labour_attendance()
    {
        $labour = $this->createTestLabour();

        $response = $this->actingAs($this->user)->post(route('labour-attendances.store'), [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-03', // Monday
            'status' => 'present',
            'notes' => 'On site early',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-03',
            'status' => 'present',
        ]);

        $this->actingAs($this->user)->post(route('labour-attendances.store'), [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-04', // Tuesday
            'status' => 'half_day',
        ]);
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-04',
            'status' => 'half_day',
        ]);

        $this->actingAs($this->user)->post(route('labour-attendances.store'), [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-05', // Wednesday
            'status' => 'absent',
        ]);
        $this->assertDatabaseHas('labour_attendances', [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-05',
            'status' => 'absent',
        ]);
    }

    /** @test */
    public function it_blocks_marking_attendance_on_sundays()
    {
        $labour = $this->createTestLabour();

        $response = $this->actingAs($this->user)->post(route('labour-attendances.store'), [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-02', // Sunday
            'status' => 'present',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('labour_attendances', [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-02',
        ]);
    }

    /** @test */
    public function it_blocks_duplicate_attendance_for_same_labour_and_date()
    {
        $labour = $this->createTestLabour();

        LabourAttendance::create([
            'labour_id' => $labour->id,
            'employee_id' => $this->user->id,
            'attendance_date' => '2026-08-03',
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->user)->post(route('labour-attendances.store'), [
            'labour_id' => $labour->id,
            'attendance_date' => '2026-08-03',
            'status' => 'absent',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1, LabourAttendance::where('labour_id', $labour->id)->where('attendance_date', '2026-08-03')->count());
    }

    /** @test */
    public function it_supports_bulk_attendance_for_multiple_labours_on_same_date()
    {
        $labour1 = $this->createTestLabour(['name' => 'Labour One']);
        $labour2 = $this->createTestLabour(['name' => 'Labour Two']);

        $response = $this->actingAs($this->user)->post(route('labour-attendances.bulk-store'), [
            'attendance_date' => '2026-08-03',
            'attendances' => [
                ['labour_id' => $labour1->id, 'status' => 'present', 'notes' => 'P1'],
                ['labour_id' => $labour2->id, 'status' => 'half_day', 'notes' => 'P2'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('labour_attendances', ['labour_id' => $labour1->id, 'status' => 'present']);
        $this->assertDatabaseHas('labour_attendances', ['labour_id' => $labour2->id, 'status' => 'half_day']);
    }

    /** @test */
    public function it_calculates_monthly_mon_sat_working_days_and_payable_salary()
    {
        $labour = $this->createTestLabour();

        // August 2026 has 31 days, 5 Sundays => 26 Mon-Sat working days.
        // Mark 10 present, 2 half_day, 1 absent => payable_days = 10 + 1 = 11.
        for ($day = 3; $day <= 12; $day++) {
            $dateStr = sprintf('2026-08-%02d', $day);
            LabourAttendance::create([
                'labour_id' => $labour->id,
                'employee_id' => $this->user->id,
                'attendance_date' => $dateStr,
                'status' => 'present',
            ]);
        }
        LabourAttendance::create([
            'labour_id' => $labour->id,
            'employee_id' => $this->user->id,
            'attendance_date' => '2026-08-13',
            'status' => 'half_day',
        ]);
        LabourAttendance::create([
            'labour_id' => $labour->id,
            'employee_id' => $this->user->id,
            'attendance_date' => '2026-08-14',
            'status' => 'half_day',
        ]);

        $summary = \App\Http\Controllers\LabourAttendanceController::calculateMonthlySummary($labour, '2026-08');

        $this->assertEquals(26, $summary['total_working_days']);
        $this->assertEquals(10, $summary['present_days']);
        $this->assertEquals(2, $summary['half_days']);
        $this->assertEquals(11, $summary['payable_days']); // 10 + 2*0.5
        $this->assertEquals(11000.00, $summary['calculated_salary']); // (26000 / 26) * 11
    }

    /** @test */
    public function it_gives_labour_advance_and_debits_company_wallet()
    {
        $labour = $this->createTestLabour(['advance_amt' => 0]);

        $initialWallet = (float) $this->user->fresh()->wallet; // 50000

        $response = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'entry_type' => 'credit',
            'labour_id' => $labour->id,
            'amount' => 5000,
            'notes' => 'Site advance',
        ]);

        $response->assertRedirect();
        $this->assertEquals(5000.00, (float) $labour->fresh()->advance_amt);
        $this->assertEquals($initialWallet - 5000, (float) $this->user->fresh()->wallet);
        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $labour->id,
            'entry_type' => 'credit',
            'amount' => 5000.00,
        ]);
    }

    /** @test */
    public function it_rejects_labour_advance_if_company_wallet_has_insufficient_balance()
    {
        \Illuminate\Support\Facades\DB::table('users')->where('id', $this->user->id)->update(['wallet' => 1000.00]);
        $labour = $this->createTestLabour(['advance_amt' => 0]);

        $response = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'entry_type' => 'credit',
            'labour_id' => $labour->id,
            'amount' => 5000,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(0.00, (float) $labour->fresh()->advance_amt);
        $this->assertEquals(1000.00, (float) $this->user->fresh()->wallet);
        $this->assertDatabaseMissing('advance_history', ['labour_id' => $labour->id]);
    }

    /** @test */
    public function it_validates_advance_withdrawal_and_credits_company_wallet()
    {
        $labour = $this->createTestLabour(['advance_amt' => 5000]);
        $initialWallet = (float) $this->user->fresh()->wallet;

        // Try withdrawing 10000 (exceeds advance balance of 5000)
        $invalidResponse = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'entry_type' => 'withdraw',
            'labour_id' => $labour->id,
            'amount' => 10000,
        ]);
        $invalidResponse->assertSessionHasErrors('amount');
        $this->assertEquals(5000.00, (float) $labour->fresh()->advance_amt);

        // Valid withdrawal of 3000
        $validResponse = $this->actingAs($this->user)->post(route('labour-expenses.advance-store'), [
            'entry_type' => 'withdraw',
            'labour_id' => $labour->id,
            'amount' => 3000,
            'notes' => 'Returned unused advance',
        ]);
        $validResponse->assertRedirect();
        $this->assertEquals(2000.00, (float) $labour->fresh()->advance_amt);
        $this->assertEquals($initialWallet + 3000, (float) $this->user->fresh()->wallet);
        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $labour->id,
            'entry_type' => 'withdraw',
            'amount' => 3000.00,
        ]);
    }

    /** @test */
    public function it_settles_salary_with_advance_deduction_and_wallet_debit()
    {
        $labour = $this->createTestLabour(['advance_amt' => 5000]);
        $initialWallet = (float) $this->user->fresh()->wallet; // 50000

        // Calculated salary = 15,000, Advance adjusted = 5,000, Net Payable = 10,000, Paid = 10,000
        $response = $this->actingAs($this->user)->post(route('labour-salaries.store'), [
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-08-01',
            'salary_period_end' => '2026-08-31',
            'salary_amount' => 15000,
            'advance_adjusted' => 5000,
            'paid_amount' => 10000,
            'payment_date' => '2026-08-31',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('labour_salaries', [
            'labour_id' => $labour->id,
            'salary_amount' => 15000.00,
            'advance_adjusted' => 5000.00,
            'paid_amount' => 10000.00,
            'remaining_amount' => 0.00,
            'status' => 'paid',
        ]);

        // Labour advance balance reduced to 0
        $this->assertEquals(0.00, (float) $labour->fresh()->advance_amt);
        // Admin wallet debited only by net paid amount (10000)
        $this->assertEquals($initialWallet - 10000, (float) $this->user->fresh()->wallet);

        $this->assertDatabaseHas('advance_history', [
            'labour_id' => $labour->id,
            'entry_type' => 'settle',
            'amount' => 5000.00,
        ]);
    }

    /** @test */
    public function it_blocks_paid_period_attendance_modification()
    {
        $labour = $this->createTestLabour();

        $attendance = LabourAttendance::create([
            'labour_id' => $labour->id,
            'employee_id' => $this->user->id,
            'attendance_date' => '2026-08-10',
            'status' => 'present',
        ]);

        LabourSalary::create([
            'labour_id' => $labour->id,
            'salary_period_start' => '2026-08-01',
            'salary_period_end' => '2026-08-31',
            'salary_amount' => 10000,
            'paid_amount' => 10000,
            'payment_date' => '2026-08-31',
            'status' => 'paid',
        ]);

        // Attempting to delete attendance for paid period
        $response = $this->actingAs($this->user)->delete(route('labour-attendances.destroy', $attendance));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('labour_attendances', ['id' => $attendance->id]);
    }
}
