<?php

namespace App\Console\Commands;

use App\Services\TrackingReprocessService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ReprocessTracking extends Command
{
    protected $signature = 'tracking:reprocess
        {--employee= : Employee/user id to reprocess}
        {--date= : Attendance date to reprocess, for example 2026-07-22}
        {--all : Reprocess every attendance session}
        {--dry-run : Build diagnostics without writing processing flags}';

    protected $description = 'Reprocess old employee tracking rows into accepted/ignored route segments without deleting raw GPS data.';

    public function handle(TrackingReprocessService $service): int
    {
        $employeeId = $this->option('employee') !== null ? (int) $this->option('employee') : null;
        $date = $this->option('date') ? (string) $this->option('date') : null;
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $service->reprocess($employeeId, $date, $all, $dryRun);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = $result['summary'];
        $this->info($dryRun ? 'Tracking reprocess dry run completed.' : 'Tracking reprocess completed.');
        $this->line('Attendance sessions: ' . $summary['attendance_count']);
        $this->line('Tracking rows: ' . $summary['tracking_rows']);
        $this->line('Accepted rows: ' . $summary['accepted_rows']);
        $this->line('Ignored rows: ' . $summary['ignored_rows']);
        $this->line('Route segments: ' . $summary['route_segments']);
        $this->line('Route points: ' . $summary['route_points']);
        $this->line('GPS distance: ' . number_format((float) $summary['gps_distance_km'], 2) . ' KM');
        $this->line('Flags written: ' . ($summary['flags_written'] ? 'yes' : 'no'));

        if (! empty($summary['rejection_reasons'])) {
            $this->line('Ignored reasons:');
            foreach ($summary['rejection_reasons'] as $reason => $count) {
                $this->line(' - ' . $reason . ': ' . $count);
            }
        }

        if (! empty($result['sessions'])) {
            $this->table(
                ['Attendance', 'Employee', 'Date', 'Rows', 'Accepted', 'Ignored', 'Segments', 'Points', 'KM'],
                collect($result['sessions'])->map(fn (array $session): array => [
                    $session['attendance_id'],
                    $session['employee_id'],
                    $session['attendance_date'],
                    $session['tracking_rows'],
                    $session['accepted_rows'],
                    $session['ignored_rows'],
                    $session['route_segments'],
                    $session['route_points'],
                    number_format((float) $session['gps_distance_km'], 2),
                ])->all()
            );
        }

        return self::SUCCESS;
    }
}
