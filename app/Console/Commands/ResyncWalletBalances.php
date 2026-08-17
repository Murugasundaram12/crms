<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResyncWalletBalances extends Command
{
    protected $signature = 'wallet:resync-balances {--apply : Apply the calculated ledger balances to users.wallet}';
    protected $description = 'Resynchronize users.wallet column values with canonical wallet ledger net balances';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info("==================================================");
        $this->info("WALLET BALANCE RESYNC AUDIT (" . ($apply ? "APPLY MODE" : "DRY RUN MODE") . ")");
        $this->info("==================================================");

        $users = User::query()->orderBy('id')->get();
        $discrepancyCount = 0;
        $updatedCount = 0;

        $rows = [];

        foreach ($users as $user) {
            $userWallet = (float) $user->wallet;
            $userId = (int) $user->id;

            $credits = (float) DB::table('wallet')
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('transfer_type', 0)->orWhere('transfer_type', '0');
                })
                ->sum('amount');

            $debits = (float) DB::table('wallet')
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('transfer_type', 1)->orWhere('transfer_type', '1');
                })
                ->sum('amount');

            $expectedLedgerNet = $credits - $debits;
            $difference = $expectedLedgerNet - $userWallet;

            $hasDrift = abs($difference) >= 0.01;

            if ($hasDrift) {
                $discrepancyCount++;
            }

            $rows[] = [
                'ID' => $userId,
                'Name' => substr($user->name, 0, 25),
                'Current wallet' => number_format($userWallet, 2),
                'Ledger Net' => number_format($expectedLedgerNet, 2),
                'Diff' => number_format($difference, 2),
                'Status' => $hasDrift ? 'DRIFT 🚨' : 'MATCH ✅',
            ];
        }

        $this->table(['ID', 'Name', 'Current wallet', 'Ledger Net', 'Diff', 'Status'], $rows);

        $this->info("Total users inspected: " . count($users));
        $this->info("Discrepancy count: {$discrepancyCount}");

        if (! $apply) {
            $this->comment("DRY RUN COMPLETE: Zero database modifications performed. Use --apply to update users.wallet.");
            return self::SUCCESS;
        }

        if ($discrepancyCount === 0) {
            $this->info("All user wallet balances match the ledger. No updates needed.");
            return self::SUCCESS;
        }

        DB::transaction(function () use ($users, &$updatedCount) {
            foreach ($users as $user) {
                $userId = (int) $user->id;

                $credits = (float) DB::table('wallet')
                    ->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->where('transfer_type', 0)->orWhere('transfer_type', '0');
                    })
                    ->sum('amount');

                $debits = (float) DB::table('wallet')
                    ->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->where('transfer_type', 1)->orWhere('transfer_type', '1');
                    })
                    ->sum('amount');

                $expectedLedgerNet = $credits - $debits;

                if (abs((float) $user->wallet - $expectedLedgerNet) >= 0.01) {
                    DB::table('users')->where('id', $userId)->update([
                        'wallet' => $expectedLedgerNet,
                    ]);
                    $updatedCount++;
                }
            }
        });

        $this->info("APPLY COMPLETE: Updated {$updatedCount} user wallet balances to match canonical ledger net balances.");

        return self::SUCCESS;
    }
}
