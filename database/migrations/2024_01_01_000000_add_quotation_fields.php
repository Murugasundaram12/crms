<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'client_id')) {
                $table->foreignId('client_id')->nullable()->constrained('clients')->after('id');
            }
            if (! Schema::hasColumn('quotations', 'validity_days')) {
                $table->integer('validity_days')->default(30)->after('status');
            }
            if (! Schema::hasColumn('quotations', 'start_date')) {
                $table->date('start_date')->nullable()->after('validity_days');
            }
            if (! Schema::hasColumn('quotations', 'duration_days')) {
                $table->integer('duration_days')->default(0)->after('start_date');
            }
            if (! Schema::hasColumn('quotations', 'notes')) {
                $table->text('notes')->nullable()->after('duration_days');
            }
            if (! Schema::hasColumn('quotations', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('notes');
            }
            if (! Schema::hasColumn('quotations', 'gst_percent')) {
                $table->decimal('gst_percent', 5, 2)->default(0)->after('discount_percent');
            }
            if (! Schema::hasColumn('quotations', 'sub_total')) {
                $table->decimal('sub_total', 14, 2)->default(0)->after('gst_percent');
            }
            if (Schema::hasColumn('quotations', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'project_id')) {
                $table->foreignId('project_id')->constrained()->onDelete('cascade')->after('id');
            }
        });
    }
};
