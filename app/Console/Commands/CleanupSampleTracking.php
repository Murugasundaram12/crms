<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LocationTracking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CleanupSampleTracking extends Command
{
    protected $signature = 'tracking:cleanup-sample
                            {--employee= : Optional employee ID}
                            {--date= : Selected test date in YYYY-MM-DD format}
                            {--confirm : Required confirmation flag}';

    protected $description = 'Clean up safe employee-tracking sample test data from the database.';

    public function handle()
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

        // 2. Find employee
        $employeeId = $this->option('employee');
        $employee = null;

        if ($employeeId) {
            $employee = User::query()->find($employeeId);
        } else {
            $employee = User::query()->where('email', 'tracking.test@housefix.local')->first();
        }

        if (! $employee) {
            $this->error("Target test employee not found in database.");
            return 1;
        }

        // Strict safety validations on the employee
        $email = strtolower((string) $employee->email);
        $name = strtolower((string) $employee->name);
        if (! Str::endsWith($email, '@housefix.local') && $name !== 'tracking test employee') {
            $this->error("Refused: Target employee '{$employee->name}' ({$employee->email}) is NOT a designated test employee.");
            return 1;
        }

        $this->info("Target Employee: ID {$employee->id} | {$employee->name} | {$employee->email}");
        $this->info("Target Date: {$testDate->toDateString()}");

        // 3. Scan for target records
        $targetAttendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereDate('attendance_date', $testDate->toDateString())
            ->where('notes', 'generated sample tracking')
            ->get();

        if ($targetAttendances->isEmpty()) {
            $this->info("No generated test attendance records found for this date/employee.");
            return 0;
        }

        $attendanceIds = $targetAttendances->pluck('id')->all();

        $targetTrackings = LocationTracking::query()
            ->whereIn('attendance_id', $attendanceIds)
            ->where('employee_id', $employee->id)
            ->where('device_id', 'TEST-TRACKING-DEVICE')
            ->where('client_uuid', 'like', 'test-tracking-%')
            ->get();

        $this->warn("The following records will be DELETED:");
        $this->line("- Attendance rows: " . $targetAttendances->count() . " records (IDs: " . implode(', ', $attendanceIds) . ")");
        $this->line("- LocationTracking rows: " . $targetTrackings->count() . " records");

        // Safety check to ensure we do not touch any records with other device_ids or client_uuids
        $unrelatedTrackingsCount = LocationTracking::query()
            ->whereIn('attendance_id', $attendanceIds)
            ->where(function ($query) {
                $query->where('device_id', '!=', 'TEST-TRACKING-DEVICE')
                      ->orWhere('client_uuid', 'not like', 'test-tracking-%');
            })
            ->count();

        if ($unrelatedTrackingsCount > 0) {
            $this->error("Refused: Found {$unrelatedTrackingsCount} tracking points linked to these test attendance sessions that do NOT match the strict test tags.");
            return 1;
        }

        // Delete records
        DB::transaction(function () use ($attendanceIds, $targetAttendances) {
            LocationTracking::query()->whereIn('attendance_id', $attendanceIds)->delete();
            Attendance::query()->whereIn('id', $attendanceIds)->delete();
        });

        $this->info("Cleanup completed successfully. Generated records deleted, database is clean.");
        return 0;
    }
}
