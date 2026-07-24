<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LocationTracking;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ArtisanTrackingSampleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $testUser;
    protected User $realUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test employee
        $this->testUser = User::create([
            'name' => 'Tracking Test Employee',
            'email' => 'tracking.test.phpunit@housefix.local',
            'phone' => '+19999999999',
            'role' => 'Employee',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);

        // Create a real employee (not matching test naming)
        $this->realUser = User::create([
            'name' => 'Real Employee',
            'email' => 'real.employee@example.com',
            'phone' => '+18888888888',
            'role' => 'Employee',
            'status' => 'active',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_refuses_to_run_against_non_test_employee(): void
    {
        $exitCode = Artisan::call('tracking:generate-sample', [
            '--employee' => $this->realUser->id,
            '--scenario' => 'basic-route',
            '--confirm' => true,
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_refuses_to_run_without_confirm(): void
    {
        $exitCode = Artisan::call('tracking:generate-sample', [
            '--employee' => $this->testUser->id,
            '--scenario' => 'basic-route',
        ]);

        $this->assertEquals(1, $exitCode);
    }

    public function test_generates_basic_route_successfully(): void
    {
        $date = '2026-07-24';
        
        $exitCode = Artisan::call('tracking:generate-sample', [
            '--employee' => $this->testUser->id,
            '--date' => $date,
            '--scenario' => 'basic-route',
            '--confirm' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Verify records in DB
        $attendance = Attendance::where('user_id', $this->testUser->id)
            ->whereDate('attendance_date', $date)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('generated sample tracking', $attendance->notes);

        $trackings = LocationTracking::where('attendance_id', $attendance->id)->get();
        $this->assertCount(12, $trackings); // check-in + 10 movement + check-out
        
        $checkIn = $trackings->firstWhere('type', 'checked_in');
        $checkOut = $trackings->firstWhere('type', 'checked_out');
        $this->assertNotNull($checkIn);
        $this->assertNotNull($checkOut);
    }

    public function test_cleanup_deletes_tagged_rows_and_refuses_untagged(): void
    {
        $date = '2026-07-24';

        // 1. Create a tagged attendance + tracking points
        $attendance = Attendance::create([
            'user_id' => $this->testUser->id,
            'attendance_date' => $date,
            'check_in_at' => now(),
            'notes' => 'generated sample tracking',
        ]);

        $tracking = LocationTracking::create([
            'attendance_id' => $attendance->id,
            'employee_id' => $this->testUser->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'client_uuid' => 'test-tracking-123',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'type' => 'checked_in',
            'recorded_at' => now(),
        ]);

        // 2. Create an untagged tracking point (simulate real data)
        $realAttendance = Attendance::create([
            'user_id' => $this->testUser->id,
            'attendance_date' => $date,
            'check_in_at' => now(),
            'notes' => 'real notes',
        ]);

        // Try cleanup on real user - should refuse
        $exitCode = Artisan::call('tracking:cleanup-sample', [
            '--employee' => $this->realUser->id,
            '--date' => $date,
            '--confirm' => true,
        ]);
        $this->assertEquals(1, $exitCode);

        // Try cleanup on test user - should succeed for tagged ones
        $exitCode = Artisan::call('tracking:cleanup-sample', [
            '--employee' => $this->testUser->id,
            '--date' => $date,
            '--confirm' => true,
        ]);
        $this->assertEquals(0, $exitCode);

        // Assert tagged are deleted
        $this->assertNull(Attendance::find($attendance->id));
        $this->assertNull(LocationTracking::find($tracking->id));

        // Assert untagged real data is preserved!
        $this->assertNotNull(Attendance::find($realAttendance->id));
    }
}
