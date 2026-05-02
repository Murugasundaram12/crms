<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('main_title')->nullable()->after('quotation_id');
            $table->unsignedInteger('main_title_order')->default(0)->after('main_title');
            $table->unsignedInteger('item_order')->default(0)->after('main_title_order');
            $table->decimal('nos', 10, 2)->nullable()->after('description');
            $table->decimal('length', 10, 2)->nullable()->after('nos');
            $table->decimal('breadth', 10, 2)->nullable()->after('length');
            $table->decimal('depth', 10, 2)->nullable()->after('breadth');
            $table->string('unit', 50)->nullable()->after('quantity');
            $table->decimal('price', 14, 2)->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn([
                'main_title',
                'main_title_order',
                'item_order',
                'nos',
                'length',
                'breadth',
                'depth',
                'unit',
                'price',
            ]);
        });
    }
};
