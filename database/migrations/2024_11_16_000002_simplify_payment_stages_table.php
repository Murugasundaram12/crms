<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_stages', function (Blueprint $table) {
            $table->renameColumn('stage_name', 'name');
            $table->dropForeign(['project_id']);
            $table->dropColumn([
                'project_id',
                'percentage',
                'amount',
                'status',
                'order'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payment_stages', function (Blueprint $table) {
            $table->renameColumn('name', 'stage_name');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('order')->default(1);
        });
    }
};
