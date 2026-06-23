<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            foreach (['budget', 'spent'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'budget')) {
                $table->decimal('budget', 14, 2)->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('projects', 'spent')) {
                $table->decimal('spent', 14, 2)->nullable()->after('budget');
            }
        });
    }
};
