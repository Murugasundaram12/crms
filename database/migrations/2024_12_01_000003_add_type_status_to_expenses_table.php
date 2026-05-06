<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('type', ['salary', 'material', 'travel', 'other'])->default('other')->after('title');
            $table->string('category')->nullable()->after('type');
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['type', 'status']);
            $table->dropColumn('category');
        });
    }
};
