<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LocationTracking;
use App\Services\EmployeeTimelineBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GenerateSampleTracking extends Command
{
    protected $signature = 'tracking:generate-sample
                            {--employee= : Optional employee ID}
                            {--date= : Selected test date in YYYY-MM-DD format}
                            {--scenario=basic-route : Test scenario (basic-route, still-split, large-gap, invalid-filtering, low-coverage)}
                            {--confirm : Required confirmation flag}';

    protected $description = 'Generate a safe employee-tracking test flow using sample data.';

    public function handle(EmployeeTimelineBuilder $timelineBuilder)
    {
        $appEnv = config('app.env');
        $dbName = config('database.connections.' . config('database.default') . '.database');

        $this->info("==================================================");
        $this->info("CRITICAL ENVIRONMENT INFORMATION");
        $this->info("==================================================");
        $this->info("APP_ENV: {$appEnv}");
        $this->info("DB_DATABASE: {$dbName}");
        $this->info("==================================================");

        if (! $this->option('confirm')) {
            $this->error("Error: --confirm option must be supplied to execute this command.");
            return 1;
        }

        // 1. Determine date
        $dateStr = $this->option('date') ?: Carbon::today()->toDateString();
        try {
            $testDate = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $this->error("Invalid date format: {$dateStr}. Please use YYYY-MM-DD.");
            return 1;
        }

        // 2. Find or create the test employee
        $employeeId = $this->option('employee');
        $employee = null;

        if ($employeeId) {
            $employee = User::query()->find($employeeId);
            if (! $employee) {
                $this->error("Employee with ID {$employeeId} not found.");
                return 1;
            }

            // Ensure the employee is a test employee
            $email = strtolower((string) $employee->email);
            $name = strtolower((string) $employee->name);
            if (! Str::endsWith($email, '@housefix.local') && $name !== 'tracking test employee') {
                $this->error("Refused: Selected employee '{$employee->name}' ({$employee->email}) is NOT a designated test employee.");
                $this->error("Test employee names must be 'Tracking Test Employee' or have emails ending with '@housefix.local'.");
                return 1;
            }
        } else {
            // Find or create default test employee
            $employee = User::query()->where('email', 'tracking.test@housefix.local')->first();
            if (! $employee) {
                $employee = User::create([
                    'name' => 'Tracking Test Employee',
                    'email' => 'tracking.test@housefix.local',
                    'phone' => '+10000000000',
                    'role' => 'Employee',
                    'status' => 'active',
                    'wallet' => 0,
                    'password' => Hash::make('password'),
                ]);
                $this->info("Created new dedicated test employee: '{$employee->name}' ({$employee->email})");
            } else {
                $this->info("Reusing existing test employee: '{$employee->name}' ({$employee->email})");
            }
        }

        $this->info("Target Employee: ID {$employee->id} | {$employee->name} | {$employee->email}");
        $this->info("Target Date: {$testDate->toDateString()}");
        $this->info("Scenario: " . $this->option('scenario'));

        // Refuse if any untagged real attendance exists for this employee and date
        $existingAttendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $testDate->toDateString())
            ->get();

        foreach ($existingAttendances as $att) {
            if ($att->notes !== 'generated sample tracking') {
                $this->error("Refused: A real attendance session (ID: {$att->id}) already exists for this employee on {$testDate->toDateString()}.");
                return 1;
            }
        }

        $scenario = $this->option('scenario');

        // Handle Scenario 4 (Invalid Point Filtering) which runs entirely in-memory
        if ($scenario === 'invalid-filtering') {
            $this->runInMemoryInvalidFilteringTest($employee, $testDate, $timelineBuilder);
            return 0;
        }

        // Show expected insert counts
        $expectedPointCount = match ($scenario) {
            'basic-route' => 12, // check-in + 10 movement + check-out
            'still-split' => 13, // check-in + 4 movement + 3 still + 4 movement + check-out
            'large-gap' => 10,   // check-in + 4 movement + gap + 4 movement + check-out
            'low-coverage' => 4, // check-in + 2 movement + check-out
            default => 0
        };

        if ($expectedPointCount === 0) {
            $this->error("Unknown scenario: {$scenario}");
            return 1;
        }

        $this->info("Expected Database Inserts:");
        $this->info("- 1 Attendance Record");
        $this->info("- {$expectedPointCount} Location Tracking Records");

        // Database inserts inside transaction (committed for frontend verification, deleted via cleanup)
        DB::beginTransaction();

        try {
            // Delete existing test records for this date/employee first to avoid duplicates
            foreach ($existingAttendances as $att) {
                LocationTracking::query()->where('attendance_id', $att->id)->delete();
                $att->delete();
            }

            // Create Attendance
            $checkInAt = $testDate->copy()->setTime(9, 0, 0);
            $checkOutAt = $checkInAt->copy()->addMinutes($expectedPointCount * 15 / 60 + 5);
            if ($scenario === 'large-gap') {
                $checkOutAt = $checkInAt->copy()->addMinutes(4 * 15 / 60 + 15 + 4 * 15 / 60 + 5);
            }

            $attendance = Attendance::create([
                'user_id' => $employee->id,
                'attendance_date' => $testDate->toDateString(),
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'worked_minutes' => $checkInAt->diffInMinutes($checkOutAt),
                'status' => 'present',
                'notes' => 'generated sample tracking',
            ]);

            $this->info("Created test attendance: ID {$attendance->id}");

            $points = $this->generateScenarioPoints($scenario, $employee->id, $attendance->id, $checkInAt);

            foreach ($points as $p) {
                LocationTracking::create($p);
            }

            DB::commit();
            $this->info("Successfully inserted {$expectedPointCount} sample tracking points into the database.");
            $this->info("You can now view this route on: " . url("/employee-tracking"));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to generate tracking records: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function generateScenarioPoints(string $scenario, int $employeeId, int $attendanceId, Carbon $startTime): array
    {
        $points = [];
        $baseLat = 11.016844;
        $baseLng = 76.955832;

        if ($scenario === 'basic-route') {
            // Check-in
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_in', 'still', $baseLat, $baseLng, $startTime->copy());

            // 10 movement points
            for ($i = 1; $i <= 10; $i++) {
                $time = $startTime->copy()->addSeconds($i * 15);
                $lat = $baseLat + ($i * 0.0002);
                $lng = $baseLng + ($i * 0.0002);
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'travelling', 'in_vehicle', $lat, $lng, $time, 10.5, 45);
            }

            // Check-out
            $endTime = $startTime->copy()->addSeconds(11 * 15);
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_out', 'still', $baseLat + (10 * 0.0002), $baseLng + (10 * 0.0002), $endTime);
        } elseif ($scenario === 'still-split') {
            // Check-in
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_in', 'still', $baseLat, $baseLng, $startTime->copy());

            // 4 movement
            for ($i = 1; $i <= 4; $i++) {
                $time = $startTime->copy()->addSeconds($i * 15);
                $lat = $baseLat + ($i * 0.0002);
                $lng = $baseLng + ($i * 0.0002);
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'travelling', 'in_vehicle', $lat, $lng, $time, 10.5, 45);
            }

            // 3 still points
            $stillLat = $baseLat + (4 * 0.0002);
            $stillLng = $baseLng + (4 * 0.0002);
            for ($i = 5; $i <= 7; $i++) {
                $time = $startTime->copy()->addSeconds($i * 15);
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'still', 'still', $stillLat, $stillLng, $time, 0, 0);
            }

            // 4 movement
            for ($i = 8; $i <= 11; $i++) {
                $time = $startTime->copy()->addSeconds($i * 15);
                $lat = $stillLat + (($i - 7) * 0.0002);
                $lng = $stillLng + (($i - 7) * 0.0002);
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'travelling', 'in_vehicle', $lat, $lng, $time, 10.5, 45);
            }

            // Check-out
            $endTime = $startTime->copy()->addSeconds(12 * 15);
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_out', 'still', $stillLat + (4 * 0.0002), $stillLng + (4 * 0.0002), $endTime);
        } elseif ($scenario === 'large-gap') {
            // Check-in
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_in', 'still', $baseLat, $baseLng, $startTime->copy());

            // 4 movement
            for ($i = 1; $i <= 4; $i++) {
                $time = $startTime->copy()->addSeconds($i * 15);
                $lat = $baseLat + ($i * 0.0002);
                $lng = $baseLng + ($i * 0.0002);
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'travelling', 'in_vehicle', $lat, $lng, $time, 10.5, 45);
            }

            // Gap of 15 minutes
            $gapStart = $startTime->copy()->addSeconds(4 * 15)->addMinutes(15);

            // 4 movement
            for ($i = 5; $i <= 8; $i++) {
                $time = $gapStart->copy()->addSeconds(($i - 5) * 15);
                $lat = $baseLat + ($i * 0.0002) + 0.01;
                $lng = $baseLng + ($i * 0.0002) + 0.01;
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'travelling', 'in_vehicle', $lat, $lng, $time, 10.5, 45);
            }

            // Check-out
            $endTime = $gapStart->copy()->addSeconds(4 * 15);
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_out', 'still', $baseLat + (8 * 0.0002) + 0.01, $baseLng + (8 * 0.0002) + 0.01, $endTime);
        } elseif ($scenario === 'low-coverage') {
            // Check-in
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_in', 'still', $baseLat, $baseLng, $startTime->copy());

            // 2 movement
            for ($i = 1; $i <= 2; $i++) {
                $time = $startTime->copy()->addSeconds($i * 15);
                $lat = $baseLat + ($i * 0.0002);
                $lng = $baseLng + ($i * 0.0002);
                $points[] = $this->buildPoint($employeeId, $attendanceId, 'travelling', 'in_vehicle', $lat, $lng, $time, 10.5, 45);
            }

            // Check-out
            $endTime = $startTime->copy()->addSeconds(3 * 15);
            $points[] = $this->buildPoint($employeeId, $attendanceId, 'checked_out', 'still', $baseLat + (2 * 0.0002), $baseLng + (2 * 0.0002), $endTime);
        }

        return $points;
    }

    private function buildPoint(int $employeeId, int $attendanceId, string $type, string $activity, float $lat, float $lng, Carbon $time, float $speed = 0, float $bearing = 0): array
    {
        return [
            'attendance_id' => $attendanceId,
            'employee_id' => $employeeId,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'client_uuid' => 'test-tracking-' . Str::uuid(),
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => 6.5,
            'speed' => $speed,
            'bearing' => $bearing,
            'activity' => $activity,
            'is_gps_on' => true,
            'is_wifi_on' => false,
            'is_mock_location' => false,
            'is_offline' => false,
            'battery_percentage' => 90,
            'type' => $type,
            'recorded_at' => $time,
        ];
    }

    private function runInMemoryInvalidFilteringTest(User $employee, Carbon $testDate, EmployeeTimelineBuilder $timelineBuilder)
    {
        $this->info("Starting Scenario 4: Invalid Point Filtering (In-Memory Check)...");

        $attendance = new Attendance([
            'user_id' => $employee->id,
            'attendance_date' => $testDate->toDateString(),
            'check_in_at' => $testDate->copy()->setTime(9, 0, 0),
            'check_out_at' => $testDate->copy()->setTime(17, 0, 0),
        ]);
        $attendance->id = 9999;

        $baseTime = $testDate->copy()->setTime(9, 0, 0);

        // 1. Poor accuracy point
        $poorAccuracy = new LocationTracking([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 60.0, // Exceeds default limit (50m)
            'type' => 'travelling',
            'activity' => 'in_vehicle',
            'recorded_at' => $baseTime->copy()->addSeconds(15),
        ]);
        $poorAccuracy->id = 1;
        $poorAccuracy->setRelation('attendance', $attendance);

        // 2. Duplicate coordinate/timestamp point (will compare against poorAccuracy if both are in collection)
        $duplicate = new LocationTracking([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 5.0,
            'type' => 'travelling',
            'activity' => 'in_vehicle',
            'recorded_at' => $baseTime->copy()->addSeconds(15), // Same timestamp
        ]);
        $duplicate->id = 2;
        $duplicate->setRelation('attendance', $attendance);

        // 3. Valid moving point to establish baseline
        $prev = new LocationTracking([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'latitude' => 11.018844, // Moving slightly
            'longitude' => 76.957832,
            'accuracy' => 5.0,
            'type' => 'travelling',
            'activity' => 'in_vehicle',
            'recorded_at' => $baseTime->copy()->addSeconds(30),
        ]);
        $prev->id = 3;
        $prev->setRelation('attendance', $attendance);

        // 4. Impossible speed point (speed_exceeded / unrealistic_jump)
        // Move by 1.2 km (approx 0.011 degrees) in 15 seconds without breaking segment (< 2.0 km)
        // 4. Impossible speed point (speed_exceeded / unrealistic_jump)
        // Move by 600m (approx 0.0055 degrees) in 15 seconds
        // Speed = 40 m/s = 144 km/h (> 120 km/h and > 90 km/h, but we will override max speed to 180 km/h to avoid segment break)
        $impossibleSpeed = new LocationTracking([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'latitude' => 11.024344, 
            'longitude' => 76.957832,
            'accuracy' => 5.0,
            'type' => 'travelling',
            'activity' => 'in_vehicle',
            'recorded_at' => $baseTime->copy()->addSeconds(45),
        ]);
        $impossibleSpeed->id = 4;
        $impossibleSpeed->setRelation('attendance', $attendance);

        // 4. Invalid latitude
        $invalidCoords = new LocationTracking([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'latitude' => 95.0, // Invalid latitude > 90
            'longitude' => 76.955832,
            'accuracy' => 5.0,
            'type' => 'travelling',
            'activity' => 'in_vehicle',
            'recorded_at' => $baseTime->copy()->addSeconds(60),
        ]);
        $invalidCoords->id = 5;
        $invalidCoords->setRelation('attendance', $attendance);

        // 5. Mock location
        $mockLocation = new LocationTracking([
            'attendance_id' => $attendance->id,
            'employee_id' => $employee->id,
            'device_id' => 'TEST-TRACKING-DEVICE',
            'latitude' => 11.016844,
            'longitude' => 76.955832,
            'accuracy' => 5.0,
            'is_mock_location' => true,
            'type' => 'travelling',
            'activity' => 'in_vehicle',
            'recorded_at' => $baseTime->copy()->addSeconds(75),
        ]);
        $mockLocation->id = 6;
        $mockLocation->setRelation('attendance', $attendance);

        // Let's run the builder
        $rawCollection = collect([
            $poorAccuracy,
            $duplicate,
            $prev,
            $impossibleSpeed,
            $invalidCoords,
            $mockLocation,
        ]);

        $res = $timelineBuilder->build($rawCollection, [
            'max_computed_speed_kmh' => 180,
        ]);
        $diagnostics = collect($res['diagnostics']);

        $this->info("DIAGNOSTICS: " . json_encode($res['diagnostics'], JSON_PRETTY_PRINT));

        $this->info("\nVerification Results:");
        $this->verifyDiagnostic($diagnostics, 1, 'accuracy_exceeded', 'Poor Accuracy Point');
        $this->verifyDiagnostic($diagnostics, 2, 'duplicate_location', 'Duplicate Point');
        $this->verifyDiagnostic($diagnostics, 4, 'unrealistic_jump', 'Impossible Speed/Jump Point');
        $this->verifyDiagnostic($diagnostics, 5, 'invalid_coordinates', 'Invalid Coordinate Point');
        $this->verifyDiagnostic($diagnostics, 6, 'mock_location', 'Mock Location Point');

        $this->info("\nInvalid filtering verification complete!");
    }

    private function verifyDiagnostic($diagnostics, int $id, string $expectedReason, string $label)
    {
        $match = $diagnostics->firstWhere('id', $id);
        if ($match && ! ($match['accepted'] ?? true) && ($match['reason'] ?? '') === $expectedReason) {
            $this->line(" - {$label}: Rejected with expected reason '{$expectedReason}' (VERIFIED)");
        } else {
            $reasonFound = $match ? ($match['reason'] ?? 'none') : 'not found';
            $this->error(" - {$label}: Verification FAILED. Expected rejection reason '{$expectedReason}', got '{$reasonFound}'.");
        }
    }
}
