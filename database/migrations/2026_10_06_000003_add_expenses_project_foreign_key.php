<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses') || ! Schema::hasTable('projects') || ! Schema::hasColumn('expenses', 'project_id')) {
            return;
        }

        // Older repair migrations may have narrowed this column to INT while
        // projects.id is BIGINT UNSIGNED. Align the types before adding the FK.
        Schema::table('expenses', function (Blueprint $table): void {
            $table->unsignedBigInteger('project_id')->nullable()->change();
        });

        $hasForeignKey = collect(Schema::getForeignKeys('expenses'))
            ->contains(fn (array $foreignKey): bool => in_array('project_id', $foreignKey['columns'] ?? [], true));

        if (! $hasForeignKey) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->dropForeign(['project_id']);
            });
        }
    }
};
