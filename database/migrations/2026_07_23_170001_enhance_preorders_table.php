<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preorders', function (Blueprint $table) {
            if (! Schema::hasColumn('preorders', 'expected_rate')) {
                $table->decimal('expected_rate', 12, 2)->nullable()->after('rate');
            }
            if (! Schema::hasColumn('preorders', 'estimated_amount')) {
                $table->decimal('estimated_amount', 12, 2)->nullable()->after('expected_rate');
            }
            if (! Schema::hasColumn('preorders', 'gst_percent')) {
                $table->decimal('gst_percent', 5, 2)->default(0)->after('estimated_amount');
            }
            if (! Schema::hasColumn('preorders', 'gst_amount')) {
                $table->decimal('gst_amount', 12, 2)->default(0)->after('gst_percent');
            }
            if (! Schema::hasColumn('preorders', 'required_date')) {
                $table->date('required_date')->nullable()->after('expected_delivery_date');
            }
            if (! Schema::hasColumn('preorders', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('required_date');
            }
            if (! Schema::hasColumn('preorders', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('preorders', 'approval_date')) {
                $table->timestamp('approval_date')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('preorders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approval_date');
            }
            if (! Schema::hasColumn('preorders', 'delivery_status')) {
                $table->string('delivery_status', 50)->default('pending')->after('rejection_reason');
            }
            if (! Schema::hasColumn('preorders', 'payment_status')) {
                $table->string('payment_status', 50)->default('unpaid')->after('delivery_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('preorders', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'expected_rate',
                'estimated_amount',
                'gst_percent',
                'gst_amount',
                'required_date',
                'purchase_date',
                'approved_by',
                'approval_date',
                'rejection_reason',
                'delivery_status',
                'payment_status',
            ]);
        });
    }
};
