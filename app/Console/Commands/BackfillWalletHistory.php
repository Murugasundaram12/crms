<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Variation;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillWalletHistory extends Command
{
    protected $signature = 'wallet:backfill-history {--dry-run : Show what would be created without inserting}';

    protected $description = 'Create wallet history records for existing paid/partial payments and approved variations';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! Schema::hasTable('wallet')) {
            $this->error('Wallet table does not exist.');

            return self::FAILURE;
        }

        $created = 0;
        $created += $this->backfillPayments($dryRun);
        $created += $this->backfillVariations($dryRun);

        if ($dryRun) {
            $this->info("Dry run complete. {$created} records would be created.");
        } else {
            $this->info("Backfill complete. {$created} wallet history records created.");
        }

        return self::SUCCESS;
    }

    private function backfillPayments(bool $dryRun): int
    {
        $count = 0;
        $payments = Payment::whereIn('status', ['partial', 'paid'])->get();

        foreach ($payments as $payment) {
            $exists = Wallet::where('description', 'like', "%Payment income credit - %{$payment->id}")
                ->orWhere(function ($q) use ($payment) {
                    $q->where('client_id', (int) $payment->client_id)
                      ->where('project_id', (int) $payment->project_id)
                      ->where('amount', (int) round((float) $payment->amount))
                      ->where('transfer_type', 0)
                      ->where('description', 'like', 'Payment income credit%');
                })->exists();

            if ($exists) {
                continue;
            }

            $count++;

            if (! $dryRun) {
                Wallet::create([
                    'user_id' => 1,
                    'client_id' => (int) $payment->client_id,
                    'project_id' => (int) $payment->project_id,
                    'amount' => (int) round((float) $payment->amount),
                    'payment_mode' => $payment->payment_method === 'bank_transfer' ? 2 : 1,
                    'transfer_type' => 0,
                    'stage_id' => $payment->stage_id,
                    'description' => 'Payment income credit - ' . ($payment->quotation?->quotation_number ?? $payment->payment_code ?? $payment->id),
                    'current_date' => $payment->payment_date ?? $payment->created_at ?? Carbon::now(),
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);
            }
        }

        $this->line("Payments: {$count} records " . ($dryRun ? 'would be' : '') . " created.");

        return $count;
    }

    private function backfillVariations(bool $dryRun): int
    {
        $count = 0;
        $variations = Variation::where('status', 'approved')
            ->whereNotNull('approved_by')
            ->where('amount', '>', 0)
            ->get();

        foreach ($variations as $variation) {
            $project = $variation->project;
            if (! $project?->client_id) {
                continue;
            }

            $transferType = $variation->type === 'deduction' ? 1 : 0;

            $exists = Wallet::where('description', 'like', "Variation%{$variation->id}")
                ->orWhere(function ($q) use ($variation, $project, $transferType) {
                    $q->where('client_id', (int) $project->client_id)
                      ->where('project_id', (int) $variation->project_id)
                      ->where('amount', (int) round((float) $variation->amount))
                      ->where('transfer_type', $transferType)
                      ->where('description', 'like', 'Variation%');
                })->exists();

            if ($exists) {
                continue;
            }

            $count++;

            if (! $dryRun) {
                Wallet::create([
                    'user_id' => 1,
                    'client_id' => (int) $project->client_id,
                    'project_id' => (int) $variation->project_id,
                    'amount' => (int) round((float) $variation->amount),
                    'payment_mode' => 1,
                    'transfer_type' => $transferType,
                    'stage_id' => null,
                    'description' => 'Variation ' . ($variation->type === 'deduction' ? 'deduction' : 'addition') . ' - ' . $variation->description,
                    'current_date' => $variation->date ? Carbon::parse($variation->date) : Carbon::now(),
                    'active_status' => 1,
                    'delete_status' => 0,
                ]);
            }
        }

        $this->line("Variations: {$count} records " . ($dryRun ? 'would be' : '') . " created.");

        return $count;
    }
}
