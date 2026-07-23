<?php

namespace App\Console\Commands;

use App\Services\TrackingReprocessService;
use Illuminate\Console\Command;
use Throwable;

class DiagnoseOldTracking extends Command
{
    protected $signature = 'tracking:diagnose-old {employee_id : Employee/user id} {date : Timeline date, for example 2026-07-22}';

    protected $description = 'Diagnose old employee tracking history without modifying production location records.';

    public function handle(TrackingReprocessService $service): int
    {
        try {
            $result = $service->diagnoseOld((int) $this->argument('employee_id'), (string) $this->argument('date'));
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = $result['summary'];
        $this->info('Old tracking diagnosis completed. No records were modified.');
        $this->line('Employee: ' . ($summary['employee_name'] ?: $summary['employee_id']));
        $this->line('Date: ' . $summary['date']);
        $this->line('Attendance sessions: ' . $summary['attendance_count']);
        $this->line('Raw records: ' . $summary['raw_record_count']);
        $this->line('Accepted records: ' . $summary['accepted_count']);
        $this->line('Rejected records: ' . $summary['rejected_count']);
        $this->line('Route segments: ' . $summary['segment_count']);
        $this->line('Old raw distance: ' . number_format((float) $summary['raw_distance_km'], 2) . ' KM');
        $this->line('Cleaned GPS distance: ' . number_format((float) $summary['cleaned_distance_km'], 2) . ' KM');

        if (! empty($summary['rejected_by_reason'])) {
            $this->line('Rejected by reason:');
            foreach ($summary['rejected_by_reason'] as $reason => $count) {
                $this->line(' - ' . $reason . ': ' . $count);
            }
        }

        if (! empty($summary['problematic_record_ids'])) {
            $this->line('Problematic record IDs: ' . implode(', ', array_slice($summary['problematic_record_ids'], 0, 80)));
        }

        foreach ($result['sessions'] as $session) {
            $this->newLine();
            $this->info('Attendance #' . $session['attendance_id']);
            $this->table(
                ['Rows', 'Accepted', 'Rejected', 'Segments', 'Route points', 'Old KM', 'Cleaned KM'],
                [[
                    $session['raw_record_count'],
                    $session['accepted_count'],
                    $session['rejected_count'],
                    $session['segment_count'],
                    $session['route_point_count'],
                    number_format((float) $session['raw_distance_km'], 2),
                    number_format((float) $session['cleaned_distance_km'], 2),
                ]]
            );

            if (! empty($session['largest_time_gaps'])) {
                $this->line('Largest time gaps:');
                $this->table(
                    ['From', 'To', 'From Time', 'To Time', 'Gap Sec'],
                    collect($session['largest_time_gaps'])->map(fn (array $gap): array => [
                        $gap['from_id'],
                        $gap['to_id'],
                        $gap['from_recorded_at'],
                        $gap['to_recorded_at'],
                        $gap['gap_seconds'],
                    ])->all()
                );
            }

            if (! empty($session['largest_raw_steps'])) {
                $this->line('Largest raw jumps:');
                $this->table(
                    ['From', 'To', 'From Time', 'To Time', 'Metres', 'Gap Sec', 'KM/H'],
                    collect($session['largest_raw_steps'])->map(fn (array $step): array => [
                        $step['from_id'],
                        $step['to_id'],
                        $step['from_recorded_at'],
                        $step['to_recorded_at'],
                        $step['distance_metres'],
                        $step['gap_seconds'],
                        $step['speed_kmph'],
                    ])->all()
                );
            }
        }

        return self::SUCCESS;
    }
}
