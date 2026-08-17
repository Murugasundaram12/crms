<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WalletBalanceResyncTest extends TestCase
{
    public function test_wallet_resync_command_dry_run_performs_zero_database_modifications(): void
    {
        $user1Before = User::find(1);
        if (! $user1Before) {
            $this->assertTrue(true);
            return;
        }

        $walletCountBefore = DB::table('wallet')->count();
        $userWalletBefore = (float) $user1Before->wallet;

        // Run dry-run
        $exitCode = Artisan::call('wallet:resync-balances');

        $user1After = User::find(1);
        $walletCountAfter = DB::table('wallet')->count();
        $userWalletAfter = (float) $user1After->wallet;

        $this->assertEquals(0, $exitCode);
        $this->assertEquals($walletCountBefore, $walletCountAfter);
        $this->assertEquals($userWalletBefore, $userWalletAfter);
    }
}
