<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('job_title');
            $table->string('government_image')->nullable()->after('government_photo');
            $table->decimal('advance_amt', 14, 2)->default(0)->after('salary');
            $table->string('labour_role')->nullable()->after('advance_amt');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'government_image', 'advance_amt', 'labour_role']);
        });
    }
};

