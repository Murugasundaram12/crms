<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceLeaveIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $admin;
    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leaveType = LeaveType::query()->create([
            'name' => 'Casual Leave',
            'status' => 'active',
        ]);

        $this->employee = User::query()->create([
            'name' => 'Regular Employee',
            'email' => 'employee@example.com',
            'role' => 'Employee',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'Super Admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Test 1 — Normal employee: No leave, Check-in -> SUCCESS
     */
    public function test_normal_employee_without_leave_can_check_in_via_web(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');
        try {
            $response = $this->actingAs($this->employee)
                ->post('/attendance/check-in');

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success', 'Checked in successfully.');

            $this->assertDatabaseHas('attendances', [
                'user_id' => $this->employee->id,
                'attendance_date' => '2026-10-05',
                'status' => 'present',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Test 2 — Approved one-day leave: Approved leave 2026-10-05, Check-in -> BLOCKED
     */
    public function test_approved_one_day_leave_blocks_web_check_in(): void
    {
        LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-05',
            'status' => 'approved',
            'created_by_id' => $this->employee->id,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        Carbon::setTestNow('2026-10-05 09:00:00');
        try {
            $response = $this->actingAs($this->employee)
                ->post('/attendance/check-in');

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('error', 'You are on approved leave today and cannot check in.');

            $this->assertDatabaseMissing('attendances', [
                'user_id' => $this->employee->id,
                'attendance_date' => '2026-10-05',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Test 3 — Pending leave: Pending leave 2026-10-05, Check-in -> SUCCESS
     */
    public function test_pending_leave_does_not_block_check_in(): void
    {
        LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-05',
            'status' => 'pending',
            'created_by_id' => $this->employee->id,
        ]);

        Carbon::setTestNow('2026-10-05 09:00:00');
        try {
            $response = $this->actingAs($this->employee)
                ->post('/attendance/check-in');

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success', 'Checked in successfully.');

            $this->assertDatabaseHas('attendances', [
                'user_id' => $this->employee->id,
                'attendance_date' => '2026-10-05',
                'status' => 'present',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Test 4 — Rejected leave: Rejected leave 2026-10-05, Check-in -> SUCCESS
     */
    public function test_rejected_leave_does_not_block_check_in(): void
    {
        LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-05',
            'status' => 'rejected',
            'created_by_id' => $this->employee->id,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        Carbon::setTestNow('2026-10-05 09:00:00');
        try {
            $response = $this->actingAs($this->employee)
                ->post('/attendance/check-in');

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success', 'Checked in successfully.');

            $this->assertDatabaseHas('attendances', [
                'user_id' => $this->employee->id,
                'attendance_date' => '2026-10-05',
                'status' => 'present',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Test 5 — Multi-day approved leave: 2026-10-05 to 2026-10-07
     * 10-05 -> BLOCK
     * 10-06 -> BLOCK
     * 10-07 -> BLOCK
     * 10-08 -> ALLOW
     */
    public function test_multi_day_approved_leave_blocks_entire_range_and_allows_after(): void
    {
        LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-07',
            'status' => 'approved',
            'created_by_id' => $this->employee->id,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Day 1: 2026-10-05 -> Blocked
        Carbon::setTestNow('2026-10-05 09:00:00');
        $this->actingAs($this->employee)
            ->post('/attendance/check-in')
            ->assertSessionHas('error', 'You are on approved leave today and cannot check in.');
        $this->assertDatabaseMissing('attendances', ['user_id' => $this->employee->id, 'attendance_date' => '2026-10-05']);

        // Day 2: 2026-10-06 -> Blocked
        Carbon::setTestNow('2026-10-06 09:00:00');
        $this->actingAs($this->employee)
            ->post('/attendance/check-in')
            ->assertSessionHas('error', 'You are on approved leave today and cannot check in.');
        $this->assertDatabaseMissing('attendances', ['user_id' => $this->employee->id, 'attendance_date' => '2026-10-06']);

        // Day 3: 2026-10-07 -> Blocked
        Carbon::setTestNow('2026-10-07 09:00:00');
        $this->actingAs($this->employee)
            ->post('/attendance/check-in')
            ->assertSessionHas('error', 'You are on approved leave today and cannot check in.');
        $this->assertDatabaseMissing('attendances', ['user_id' => $this->employee->id, 'attendance_date' => '2026-10-07']);

        // Day 4: 2026-10-08 -> Allowed
        Carbon::setTestNow('2026-10-08 09:00:00');
        $this->actingAs($this->employee)
            ->post('/attendance/check-in')
            ->assertSessionHas('success', 'Checked in successfully.');
        $this->assertDatabaseHas('attendances', ['user_id' => $this->employee->id, 'attendance_date' => '2026-10-08']);

        Carbon::setTestNow();
    }

    /**
     * Test 6 — Check-out continues working for valid check-in
     */
    public function test_existing_checkout_behavior_continues_working(): void
    {
        Carbon::setTestNow('2026-10-05 09:00:00');
        try {
            $this->actingAs($this->employee)->post('/attendance/check-in');

            Carbon::setTestNow('2026-10-05 17:00:00');
            $response = $this->actingAs($this->employee)->post('/attendance/check-out');

            $response->assertRedirect(route('dashboard'));
            $response->assertSessionHas('success', 'Checked out successfully.');

            $attendance = Attendance::query()->where('user_id', $this->employee->id)->whereDate('attendance_date', '2026-10-05')->first();
            $this->assertNotNull($attendance->check_out_at);
            $this->assertSame(480, $attendance->worked_minutes);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Test 7 — Mobile API:
     * - Check-in with approved leave -> 422 blocked
     * - Check-in with pending leave -> 201 allowed
     * - Check-in with no leave -> 201 allowed
     * - Attendance status reports on_leave correctly
     */
    public function test_mobile_api_consistency_with_leave_rules(): void
    {
        $token = $this->postJson('/api/login', [
            'email' => $this->employee->email,
            'password' => 'password',
            'device_name' => 'API Test Device',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer ' . $token];

        // 1. Approved leave blocks API check-in
        LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-05',
            'status' => 'approved',
            'created_by_id' => $this->employee->id,
            'approved_by_id' => $this->admin->id,
            'approved_at' => now(),
        ]);

        Carbon::setTestNow('2026-10-05 09:00:00');
        try {
            // Check status endpoint
            $this->withHeaders($headers)
                ->getJson('/api/attendance/status')
                ->assertOk()
                ->assertJsonPath('is_on_leave', true)
                ->assertJsonPath('can_check_in', false)
                ->assertJsonPath('status', 'on_leave');

            // Check in endpoint blocked
            $this->withHeaders($headers)
                ->postJson('/api/check_in', [
                    'device_id' => 'test-device-1',
                ])
                ->assertStatus(422)
                ->assertJsonPath('message', 'You are on approved leave today and cannot check in.');

            $this->assertDatabaseMissing('attendances', [
                'user_id' => $this->employee->id,
                'attendance_date' => '2026-10-05',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        // 2. Next day (2026-10-06) with pending leave -> API check-in succeeds
        LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-06',
            'to_date' => '2026-10-06',
            'status' => 'pending',
            'created_by_id' => $this->employee->id,
        ]);

        Carbon::setTestNow('2026-10-06 09:00:00');
        try {
            $this->withHeaders($headers)
                ->postJson('/api/check_in', [
                    'device_id' => 'test-device-1',
                ])
                ->assertCreated()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('attendances', [
                'user_id' => $this->employee->id,
                'attendance_date' => '2026-10-06',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Test 8 — Existing attendance + leave approval:
     * Attendance exists + Leave is approved for same date -> BLOCKED with conflict error, attendance record NOT deleted.
     */
    public function test_approving_leave_when_attendance_exists_blocks_approval_and_preserves_attendance(): void
    {
        // 1. Create existing attendance on 2026-10-05
        $existingAttendance = Attendance::query()->create([
            'user_id' => $this->employee->id,
            'attendance_date' => '2026-10-05',
            'check_in_at' => Carbon::parse('2026-10-05 09:00:00'),
            'status' => 'present',
        ]);

        // 2. Create pending leave request covering 2026-10-05 to 2026-10-06
        $leaveRequest = LeaveRequest::query()->create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-06',
            'status' => 'pending',
            'created_by_id' => $this->employee->id,
        ]);

        // 3. Admin attempts to approve via Web
        $response = $this->actingAs($this->admin)
            ->post("/leave-requests/{$leaveRequest->id}/status", [
                'status' => 'approved',
                'approverRemarks' => 'Approved by admin',
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Attendance record already exists', session('error'));

        // Verify leave request remains pending
        $this->assertSame('pending', $leaveRequest->fresh()->status);

        // Verify existing attendance record was NOT deleted
        $this->assertDatabaseHas('attendances', [
            'id' => $existingAttendance->id,
            'user_id' => $this->employee->id,
            'attendance_date' => '2026-10-05',
            'status' => 'present',
        ]);

        // 4. Admin attempts to approve via Mobile API
        $token = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
            'device_name' => 'Admin Test Device',
        ])->json('token');

        $apiResponse = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson("/api/leave-requests/{$leaveRequest->id}/action", [
                'status' => 'approved',
            ]);

        $apiResponse->assertStatus(422)
            ->assertJsonPath('conflict_dates.0', '2026-10-05');

        // Verify attendance record still intact
        $this->assertDatabaseHas('attendances', ['id' => $existingAttendance->id]);
        $this->assertSame('pending', $leaveRequest->fresh()->status);
    }
}
