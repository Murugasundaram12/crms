<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'wallet')) {
                $table->decimal('wallet', 14, 2)->default(0)->after('status');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'wallet')) {
                $table->decimal('wallet', 14, 2)->default(0)->after('status');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'advance_amt')) {
                $table->decimal('advance_amt', 14, 2)->default(0)->after('location');
            }
            if (! Schema::hasColumn('projects', 'profit')) {
                $table->decimal('profit', 14, 2)->default(0)->after('advance_amt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            foreach (['advance_amt', 'profit'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'wallet')) {
                $table->dropColumn('wallet');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'wallet')) {
                $table->dropColumn('wallet');
            }
        });
    }
};
