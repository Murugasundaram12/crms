<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'salary_name')) {
                $table->string('salary_name')->nullable()->after('avatar');
            }
            if (! Schema::hasColumn('employees', 'salary_amount')) {
                $table->decimal('salary_amount', 10, 2)->nullable()->after('salary_name');
            }
            if (! Schema::hasColumn('employees', 'salary_type')) {
                $table->enum('salary_type', ['daily', 'weekly', 'monthly'])->nullable()->after('salary_amount');
            }
        });

        Schema::table('expenses_unpaid_date', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses_unpaid_date', 'vendor_expense_transaction_id')) {
                $table->unsignedBigInteger('vendor_expense_transaction_id')->nullable()->after('expense_id');
                $table->index('vendor_expense_transaction_id', 'exp_unpaid_vendor_tx_idx');
            }
        });

        Schema::table('advance_history', function (Blueprint $table) {
            if (! Schema::hasColumn('advance_history', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('labour_id');
                $table->index('vendor_id', 'advance_history_vendor_id_idx');
            }
        });

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'total_amount')) {
                $table->decimal('total_amount', 14, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('quotations', 'sub_total')) {
                $table->decimal('sub_total', 14, 2)->default(0)->after('total_amount');
            }
            if (! Schema::hasColumn('quotations', 'status')) {
                $table->string('status')->default('draft')->after('sub_total');
            }
            if (! Schema::hasColumn('quotations', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('status');
            }
            if (! Schema::hasColumn('quotations', 'gst_percent')) {
                $table->decimal('gst_percent', 5, 2)->default(0)->after('discount_percent');
            }
        });

        if (Schema::hasColumn('quotations', 'amount')) {
            DB::table('quotations')
                ->where(function ($query) {
                    $query->whereNull('total_amount')->orWhere('total_amount', 0);
                })
                ->update(['total_amount' => DB::raw('COALESCE(amount, 0)')]);

            DB::table('quotations')
                ->where(function ($query) {
                    $query->whereNull('sub_total')->orWhere('sub_total', 0);
                })
                ->update(['sub_total' => DB::raw('COALESCE(amount, 0)')]);
        }
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach (['gst_percent', 'discount_percent', 'status', 'sub_total', 'total_amount'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('advance_history', function (Blueprint $table) {
            if (Schema::hasColumn('advance_history', 'vendor_id')) {
                $table->dropIndex('advance_history_vendor_id_idx');
                $table->dropColumn('vendor_id');
            }
        });

        Schema::table('expenses_unpaid_date', function (Blueprint $table) {
            if (Schema::hasColumn('expenses_unpaid_date', 'vendor_expense_transaction_id')) {
                $table->dropIndex('exp_unpaid_vendor_tx_idx');
                $table->dropColumn('vendor_expense_transaction_id');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            foreach (['salary_type', 'salary_amount', 'salary_name'] as $column) {
                if (Schema::hasColumn('employees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
