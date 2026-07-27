<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $table->date('from_date');
                $table->date('to_date');
                $table->text('document')->nullable();
                $table->text('remarks')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('approver_remarks')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['leave_type_id', 'status']);
            });
        }

        foreach (['Casual Leave', 'Sick Leave', 'Permission'] as $name) {
            DB::table('leave_types')->updateOrInsert(
                ['name' => $name],
                ['status' => 'active', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};
