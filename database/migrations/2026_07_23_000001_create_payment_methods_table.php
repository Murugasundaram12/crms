<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->unique();
                $table->string('type')->nullable();
                $table->boolean('active_status')->default(true);
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        $seeds = [
            ['name' => 'CASH', 'code' => 'CASH', 'sort_order' => 1],
            ['name' => 'HDFC', 'code' => 'HDFC', 'sort_order' => 2],
            ['name' => 'SBI', 'code' => 'SBI', 'sort_order' => 3],
            ['name' => 'GPAY', 'code' => 'GPAY', 'sort_order' => 4],
            ['name' => 'PHONE PE', 'code' => 'PHONE_PE', 'sort_order' => 5],
            ['name' => 'KVBL', 'code' => 'KVBL', 'sort_order' => 6],
            ['name' => 'KOTAK MAHINDRA', 'code' => 'KOTAK_MAHINDRA', 'sort_order' => 7],
            ['name' => 'TMB', 'code' => 'TMB', 'sort_order' => 8],
            ['name' => 'EQUITAS', 'code' => 'EQUITAS', 'sort_order' => 9],
            ['name' => 'BANK TRANSFER', 'code' => 'BANK_TRANSFER', 'sort_order' => 10],
            ['name' => 'UPI', 'code' => 'UPI', 'sort_order' => 11],
            ['name' => 'NEFT', 'code' => 'NEFT', 'sort_order' => 12],
            ['name' => 'RTGS', 'code' => 'RTGS', 'sort_order' => 13],
            ['name' => 'IMPS', 'code' => 'IMPS', 'sort_order' => 14],
            ['name' => 'CHEQUE', 'code' => 'CHEQUE', 'sort_order' => 15],
            ['name' => 'CREDIT CARD', 'code' => 'CREDIT_CARD', 'sort_order' => 16],
            ['name' => 'DEBIT CARD', 'code' => 'DEBIT_CARD', 'sort_order' => 17],
        ];

        $now = now();
        foreach ($seeds as $seed) {
            $exists = DB::table('payment_methods')->where('code', $seed['code'])->exists();
            if (! $exists) {
                DB::table('payment_methods')->insert([
                    'code' => $seed['code'],
                    'name' => $seed['name'],
                    'active_status' => true,
                    'sort_order' => $seed['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('payment_methods')->where('code', $seed['code'])->update([
                    'name' => $seed['name'],
                    'active_status' => true,
                    'sort_order' => $seed['sort_order'],
                    'updated_at' => $now,
                ]);
            }
        }

        // Add permissions for payment methods & labour salaries if permissions table exists
        if (Schema::hasTable('permissions')) {
            $newPermissions = [
                ['name' => 'List Payment Methods', 'key' => 'payment-methods-list'],
                ['name' => 'Create Payment Methods', 'key' => 'payment-methods-create'],
                ['name' => 'Edit Payment Methods', 'key' => 'payment-methods-edit'],
                ['name' => 'Delete Payment Methods', 'key' => 'payment-methods-delete'],
                ['name' => 'List Labour Salaries', 'key' => 'labour-salaries-list'],
                ['name' => 'Create Labour Salaries', 'key' => 'labour-salaries-create'],
                ['name' => 'Edit Labour Salaries', 'key' => 'labour-salaries-edit'],
                ['name' => 'Delete Labour Salaries', 'key' => 'labour-salaries-delete'],
            ];

            foreach ($newPermissions as $perm) {
                $exists = DB::table('permissions')->where('key', $perm['key'])->exists();
                if (! $exists) {
                    DB::table('permissions')->insert([
                        'key' => $perm['key'],
                        'name' => $perm['name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('permissions')->where('key', $perm['key'])->update([
                        'name' => $perm['name'],
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
