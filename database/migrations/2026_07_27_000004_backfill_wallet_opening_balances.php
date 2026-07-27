<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallet') || ! Schema::hasTable('users') || ! Schema::hasColumn('users', 'wallet')) {
            return;
        }

        $users = DB::table('users')
            ->where('wallet', '!=', 0)
            ->get(['id', 'wallet', 'created_at', 'updated_at']);

        foreach ($users as $user) {
            $alreadyHasHistory = DB::table('wallet')
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyHasHistory) {
                continue;
            }

            $amount = abs((float) $user->wallet);

            DB::table('wallet')->insert([
                'user_id' => $user->id,
                'client_id' => null,
                'project_id' => null,
                'amount' => (int) round($amount),
                'payment_mode' => 1,
                'payment_method_id' => DB::table('payment_methods')->orderBy('id')->value('id'),
                'transfer_type' => (float) $user->wallet >= 0 ? 0 : 1,
                'stage_id' => null,
                'description' => 'Opening wallet balance synced from user balance',
                'current_date' => $user->updated_at ?? $user->created_at ?? now(),
                'source_type' => 'opening_balance',
                'source_id' => $user->id,
                'created_by' => null,
                'active_status' => 1,
                'delete_status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallet')) {
            return;
        }

        DB::table('wallet')
            ->where('source_type', 'opening_balance')
            ->where('description', 'Opening wallet balance synced from user balance')
            ->delete();
    }
};
