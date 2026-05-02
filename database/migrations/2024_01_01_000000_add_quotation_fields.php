<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('clients')->after('id');
            $table->integer('validity_days')->default(30)->after('status');
            $table->date('start_date')->nullable()->after('validity_days');
            $table->integer('duration_days')->default(0)->after('start_date');
            $table->text('notes')->nullable()->after('duration_days');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('notes');
            $table->decimal('gst_percent', 5, 2)->default(0)->after('discount_percent');
            $table->decimal('sub_total', 14, 2)->default(0)->after('gst_percent');
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->onDelete('cascade')->after('id');
            $table->integer('validity_days')->default(30);
            $table->date('start_date');
            $table->integer('duration_days');
            $table->text('notes');
            $table->decimal('discount_percent', 5, 2);
            $table->decimal('gst_percent', 5, 2);
            $table->decimal('sub_total', 14, 2);
        });
    }
};
