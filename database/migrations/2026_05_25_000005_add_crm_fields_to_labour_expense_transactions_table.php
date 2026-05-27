<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labour_expense_transactions', function (Blueprint $table) {
            $table->decimal('amount', 14, 2)->default(0)->after('description');
            $table->decimal('unpaid_amount', 14, 2)->default(0)->after('paid_amount');
            $table->decimal('extra_amount', 14, 2)->default(0)->after('unpaid_amount');
            $table->text('delete_reason')->nullable()->after('delete_status');
        });
    }

    public function down(): void
    {
        Schema::table('labour_expense_transactions', function (Blueprint $table) {
            $table->dropColumn(['amount', 'unpaid_amount', 'extra_amount', 'delete_reason']);
        });
    }
};

