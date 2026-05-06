<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['budget', 'spent']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget', 14, 2)->nullable()->after('end_date');
            $table->decimal('spent', 14, 2)->nullable()->after('budget');
        });
    }
};
