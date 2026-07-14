<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecalculateBalanceFields extends Command
{
    protected $signature = 'balances:recalculate {--dry-run : Show calculated totals without updating tables}';

    protected $description = 'Recalculate CRM balance fields from wallet, expenses, transfer, and advance history records.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $userBalances = $this->userBalances();
        $projectBalances = $this->projectBalances();
        $labourBalances = $this->labourBalances();
        $vendorBalances = $this->vendorBalances();

        if ($dryRun) {
            $this->line('Dry run totals:');
            $this->line('Users: ' . count($userBalances));
            $this->line('Projects: ' . count($projectBalances));
            $this->line('Labours: ' . count($labourBalances));
            $this->line('Vendors: ' . count($vendorBalances));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($userBalances, $projectBalances, $labourBalances, $vendorBalances) {
            if (Schema::hasColumn('users', 'wallet')) {
                DB::table('users')->update(['wallet' => 0]);
                foreach ($userBalances as $userId => $balance) {
                    DB::table('users')->where('id', $userId)->update(['wallet' => $balance]);
                }
            }

            if (Schema::hasColumn('employees', 'wallet')) {
                DB::table('employees')->update(['wallet' => 0]);
                DB::table('users')
                    ->select(['id', 'email', 'wallet'])
                    ->orderBy('id')
                    ->get()
                    ->each(function ($user) {
                        DB::table('employees')
                            ->where('id', $user->id)
                            ->when($user->email, fn($query) => $query->orWhere('email', $user->email))
                            ->update(['wallet' => $user->wallet]);
                    });
            }

            if (Schema::hasColumn('projects', 'advance_amt') && Schema::hasColumn('projects', 'profit')) {
                DB::table('projects')->update(['advance_amt' => 0, 'profit' => 0]);
                foreach ($projectBalances as $projectId => $balance) {
                    DB::table('projects')->where('id', $projectId)->update([
                        'advance_amt' => $balance,
                        'profit' => -$balance,
                    ]);
                }
            }

            if (Schema::hasColumn('labours', 'advance_amt')) {
                DB::table('labours')->update(['advance_amt' => 0]);
                foreach ($labourBalances as $labourId => $balance) {
                    DB::table('labours')->where('id', $labourId)->update(['advance_amt' => $balance]);
                }
            }

            if (Schema::hasColumn('vendors', 'advance_amt')) {
                DB::table('vendors')->update(['advance_amt' => 0]);
                foreach ($vendorBalances as $vendorId => $balance) {
                    DB::table('vendors')->where('id', $vendorId)->update(['advance_amt' => $balance]);
                }
            }
        });

        $this->info('Balance fields recalculated successfully.');

        return self::SUCCESS;
    }

    private function userBalances(): array
    {
        if (! Schema::hasTable('wallet')) {
            return [];
        }

        return DB::table('wallet')
            ->selectRaw('user_id, SUM(CASE WHEN transfer_type = 0 THEN amount ELSE -amount END) as balance')
            ->where('delete_status', false)
            ->groupBy('user_id')
            ->pluck('balance', 'user_id')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    private function projectBalances(): array
    {
        if (! Schema::hasTable('wallet') || ! Schema::hasColumn('wallet', 'project_id')) {
            return [];
        }

        return DB::table('wallet')
            ->selectRaw('project_id, SUM(CASE WHEN transfer_type = 0 THEN amount ELSE -amount END) as balance')
            ->where('delete_status', false)
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->pluck('balance', 'project_id')
            ->map(fn($value) => (float) $value)
            ->all();
    }

    private function labourBalances(): array
    {
        if (! Schema::hasTable('expenses')) {
            return [];
        }

        $extraByLabour = Expense::query()
            ->selectRaw('labour_id, COALESCE(SUM(extra_amt), 0) as amount')
            ->whereNotNull('labour_id')
            ->groupBy('labour_id')
            ->pluck('amount', 'labour_id')
            ->map(fn($value) => (float) $value)
            ->all();

        $settledByLabour = Schema::hasTable('advance_history') && Schema::hasColumn('advance_history', 'labour_id')
            ? DB::table('advance_history')
                ->selectRaw('labour_id, COALESCE(SUM(amount), 0) as amount')
                ->whereNotNull('labour_id')
                ->whereIn('entry_type', ['settle', 'withdraw'])
                ->groupBy('labour_id')
                ->pluck('amount', 'labour_id')
                ->map(fn($value) => (float) $value)
                ->all()
            : [];

        foreach ($settledByLabour as $labourId => $amount) {
            $extraByLabour[$labourId] = ($extraByLabour[$labourId] ?? 0) - $amount;
        }

        return array_map(fn($value) => max((float) $value, 0), $extraByLabour);
    }

    private function vendorBalances(): array
    {
        $balances = [];

        if (Schema::hasTable('transferdetails')) {
            $vendorTransfers = DB::table('transferdetails')
                ->selectRaw('vendor_id, COALESCE(SUM(amount), 0) as amount')
                ->where('delete_status', false)
                ->whereNotNull('vendor_id')
                ->groupBy('vendor_id')
                ->pluck('amount', 'vendor_id')
                ->map(fn($value) => (float) $value)
                ->all();

            foreach ($vendorTransfers as $vendorId => $amount) {
                $balances[$vendorId] = ($balances[$vendorId] ?? 0) + $amount;
            }
        }

        if (Schema::hasTable('expenses')) {
            $vendorExtras = Expense::query()
                ->selectRaw('vendor_id, COALESCE(SUM(extra_amt), 0) as amount')
                ->whereNotNull('vendor_id')
                ->groupBy('vendor_id')
                ->pluck('amount', 'vendor_id')
                ->map(fn($value) => (float) $value)
                ->all();

            foreach ($vendorExtras as $vendorId => $amount) {
                $balances[$vendorId] = ($balances[$vendorId] ?? 0) + $amount;
            }
        }

        if (Schema::hasTable('advance_history') && Schema::hasColumn('advance_history', 'vendor_id')) {
            $manualAdvance = DB::table('advance_history')
                ->selectRaw("vendor_id, COALESCE(SUM(CASE WHEN entry_type = 'withdraw' THEN -amount ELSE amount END), 0) as amount")
                ->whereNotNull('vendor_id')
                ->groupBy('vendor_id')
                ->pluck('amount', 'vendor_id')
                ->map(fn($value) => (float) $value)
                ->all();

            foreach ($manualAdvance as $vendorId => $amount) {
                $balances[$vendorId] = ($balances[$vendorId] ?? 0) + $amount;
            }
        }

        return array_map(fn($value) => max((float) $value, 0), $balances);
    }
}
