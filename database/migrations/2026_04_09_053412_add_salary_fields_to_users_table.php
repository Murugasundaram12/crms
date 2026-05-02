<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('salary_name')->nullable()->after('avatar');
            $table->decimal('salary_amount', 10, 2)->nullable()->after('salary_name');
            $table->enum('salary_type', ['daily', 'weekly', 'monthly'])->nullable()->after('salary_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['salary_name', 'salary_amount', 'salary_type']);
        });
    }
};
