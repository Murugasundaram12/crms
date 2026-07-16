<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools_materials', function (Blueprint $table) {
            $table->string('item_type', 50)->default('material')->after('id');
            $table->string('sku')->nullable()->after('item_type');
            $table->text('description')->nullable()->after('image_path');
            $table->decimal('reorder_level', 12, 2)->default(0)->after('opening_amount');
            $table->boolean('active_status')->default(true)->after('reorder_level');
        });
    }

    public function down(): void
    {
        Schema::table('tools_materials', function (Blueprint $table) {
            $table->dropColumn([
                'item_type',
                'sku',
                'description',
                'reorder_level',
                'active_status',
            ]);
        });
    }
};
