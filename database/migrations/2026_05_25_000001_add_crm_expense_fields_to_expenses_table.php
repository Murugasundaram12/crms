<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('paid_amount', 14, 2)->default(0)->after('amount');
            $table->decimal('unpaid_amount', 14, 2)->default(0)->after('paid_amount');
            $table->decimal('extra_amount', 14, 2)->default(0)->after('unpaid_amount');
            $table->boolean('active_status')->default(true)->after('description');
            $table->boolean('delete_status')->default(false)->after('active_status');
            $table->text('delete_reason')->nullable()->after('delete_status');
            $table->text('notes')->nullable()->after('delete_reason');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn([
                'paid_amount',
                'unpaid_amount',
                'extra_amount',
                'active_status',
                'delete_status',
                'delete_reason',
                'notes',
            ]);
        });
    }
};

