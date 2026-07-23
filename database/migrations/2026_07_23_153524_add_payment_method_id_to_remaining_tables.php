<?php

use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $nameMap = [
        'Cash' => 1,
        'Bank Transfer' => 10,
        'UPI' => 11,
        'Cheque' => 15,
        'Card' => 16,
        'HDFC' => 2,
        'SBI' => 3,
        'Gpay' => 4,
        'PhonePe' => 5,
        'KVBL' => 6,
        'Kotak Mahindra' => 7,
        'TMB' => 8,
        'Equitas' => 9,
    ];

    public function up(): void
    {
        // 1. expenses table
        if (! Schema::hasColumn('expenses', 'payment_method_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_mode');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            });
        }
        DB::table('expenses')->whereNotNull('payment_mode')->where('payment_mode', '>', 0)->update([
            'payment_method_id' => DB::raw("CASE payment_mode
                WHEN 1 THEN 1 WHEN 2 THEN 10 WHEN 3 THEN 11 WHEN 4 THEN 15 WHEN 5 THEN 16
                ELSE NULL END"),
        ]);

        // 2. expense_transactions table
        if (! Schema::hasColumn('expense_transactions', 'payment_method_id')) {
            Schema::table('expense_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_mode');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            });
        }
        $this->migrateStringPaymentMode('expense_transactions');

        // 3. labour_expense_transactions table
        if (! Schema::hasColumn('labour_expense_transactions', 'payment_method_id')) {
            Schema::table('labour_expense_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_mode');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            });
        }
        $this->migrateStringPaymentMode('labour_expense_transactions');

        // 4. vendor_expense_transactions table
        if (! Schema::hasColumn('vendor_expense_transactions', 'payment_method_id')) {
            Schema::table('vendor_expense_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_mode');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            });
        }
        $this->migrateStringPaymentMode('vendor_expense_transactions');

        // 5. transferdetails table
        if (Schema::hasTable('transferdetails') && ! Schema::hasColumn('transferdetails', 'payment_method_id')) {
            Schema::table('transferdetails', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_mode');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            });
        }
        if (Schema::hasTable('transferdetails')) {
            $this->migrateStringPaymentMode('transferdetails');
        }

        // 6. payments table
        if (Schema::hasColumn('payments', 'payment_method') && ! Schema::hasColumn('payments', 'payment_method_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('payment_method');
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            });
            DB::table('payments')->whereNotNull('payment_method')->update([
                'payment_method_id' => DB::raw("CASE payment_method
                    WHEN 'cash' THEN 1 WHEN 'bank_transfer' THEN 10
                    ELSE NULL END"),
            ]);
        }
    }

    public function down(): void
    {
        $tables = ['expenses', 'expense_transactions', 'labour_expense_transactions', 'vendor_expense_transactions', 'transferdetails', 'payments'];
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'payment_method_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['payment_method_id']);
                    $t->dropColumn('payment_method_id');
                });
            }
        }
    }

    private function migrateStringPaymentMode(string $table): void
    {
        foreach ($this->nameMap as $name => $id) {
            DB::table($table)->where('payment_mode', $name)->update(['payment_method_id' => $id]);
        }
    }
};
