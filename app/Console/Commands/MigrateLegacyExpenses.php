<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseTransaction;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:migrate-legacy {--apply : Apply the canonical expense migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate historical active expense_transactions into canonical expenses table';

    public const SOURCE_TYPE = 'legacy_expense_transaction';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info("==================================================");
        $this->info("HISTORICAL EXPENSE MIGRATION AUDIT (" . ($apply ? "APPLY MODE" : "DRY RUN MODE") . ")");
        $this->info("==================================================");

        $sourceTxs = DB::table('expense_transactions')
            ->whereIn('id', [1, 2])
            ->where('delete_status', 0)
            ->get();

        $this->info("Found " . count($sourceTxs) . " historical expense_transactions records to reconcile.");

        $project1Before = Project::find(1);
        $spentBefore = (float) ($project1Before?->spent ?? 0);
        $this->info("Project 1 spent before migration: ₹" . number_format($spentBefore, 2));

        $toMigrate = [];

        foreach ($sourceTxs as $tx) {
            $alreadyExists = Expense::withTrashed()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', $tx->id)
                ->exists();

            $toMigrate[] = [
                'Source ID' => $tx->id,
                'Project ID' => $tx->project_id,
                'Paid Amount' => number_format((float) $tx->paid_amount, 2),
                'Date' => $tx->current_date,
                'Description' => $tx->description,
                'Exists in expenses?' => $alreadyExists ? 'YES (Skip)' : 'NO (Will Migrate)',
            ];
        }

        $this->table(['Source ID', 'Project ID', 'Paid Amount', 'Date', 'Description', 'Exists in expenses?'], $toMigrate);

        if (! $apply) {
            $this->comment("DRY RUN COMPLETE: Zero database modifications performed. Use --apply to execute migration.");
            return self::SUCCESS;
        }

        $migratedCount = 0;

        DB::transaction(function () use ($sourceTxs, &$migratedCount) {
            foreach ($sourceTxs as $tx) {
                $alreadyExists = Expense::withTrashed()
                    ->where('source_type', self::SOURCE_TYPE)
                    ->where('source_id', $tx->id)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $paidAmt = (int) round((float) $tx->paid_amount);
                $currentDateTime = $tx->current_date;
                if (! empty($tx->current_time)) {
                    try {
                        $currentDateTime = Carbon::parse($tx->current_date . ' ' . $tx->current_time)->toDateTimeString();
                    } catch (\Exception $e) {
                        $currentDateTime = $tx->current_date;
                    }
                }

                Expense::create([
                    'user_id' => (int) $tx->user_id,
                    'main_category_id' => $tx->main_category_id ? (int) $tx->main_category_id : null,
                    'category_id' => (int) $tx->category_id,
                    'project_id' => $tx->project_id ? (int) $tx->project_id : null,
                    'description' => $tx->description,
                    'amount' => $paidAmt,
                    'paid_amt' => $paidAmt,
                    'unpaid_amt' => 0,
                    'extra_amt' => 0,
                    'payment_mode' => (int) ($tx->payment_mode === 'Cash' ? 1 : ($tx->payment_method_id ?: 1)),
                    'payment_method_id' => $tx->payment_method_id ? (int) $tx->payment_method_id : null,
                    'current_date' => $currentDateTime,
                    'image' => $tx->image_path ?? null,
                    'source_type' => self::SOURCE_TYPE,
                    'source_id' => (int) $tx->id,
                ]);

                $migratedCount++;
            }
        });

        $project1After = Project::find(1);
        $spentAfter = (float) ($project1After?->spent ?? 0);

        $this->info("APPLY COMPLETE: Successfully created {$migratedCount} canonical expense records.");
        $this->info("Project 1 spent after migration: ₹" . number_format($spentAfter, 2));

        return self::SUCCESS;
    }
}
