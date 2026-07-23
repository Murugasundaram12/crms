<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('location_trackings')) {
            return;
        }

        Schema::table('location_trackings', function (Blueprint $table): void {
            if (! Schema::hasColumn('location_trackings', 'is_ignored')) {
                $table->boolean('is_ignored')->default(false)->after('is_offline');
            }

            if (! Schema::hasColumn('location_trackings', 'ignored_reason')) {
                $table->string('ignored_reason')->nullable()->after('is_ignored');
            }

            if (! Schema::hasColumn('location_trackings', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('ignored_reason');
            }

            if (! Schema::hasColumn('location_trackings', 'segment_index')) {
                $table->unsignedInteger('segment_index')->nullable()->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('location_trackings')) {
            return;
        }

        Schema::table('location_trackings', function (Blueprint $table): void {
            foreach (['segment_index', 'processed_at', 'ignored_reason', 'is_ignored'] as $column) {
                if (Schema::hasColumn('location_trackings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
