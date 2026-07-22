<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'main_category_id')) {
                $table->integer('main_category_id')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'category_id')) {
                $table->unsignedInteger('category_id')->nullable()->after('main_category_id');
            }
            if (! Schema::hasColumn('expenses', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('project_id');
            }
            if (! Schema::hasColumn('expenses', 'current_date')) {
                $table->dateTime('current_date')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('expenses', 'paid_amt')) {
                $table->integer('paid_amt')->default(0)->after('description');
            }
            if (! Schema::hasColumn('expenses', 'unpaid_amt')) {
                $table->integer('unpaid_amt')->default(0)->after('paid_amt');
            }
            if (! Schema::hasColumn('expenses', 'extra_amt')) {
                $table->integer('extra_amt')->nullable()->after('unpaid_amt');
            }
            if (! Schema::hasColumn('expenses', 'image')) {
                $table->string('image', 250)->nullable()->after('extra_amt');
            }
            if (! Schema::hasColumn('expenses', 'editedBy')) {
                $table->integer('editedBy')->nullable()->after('image');
            }
            if (! Schema::hasColumn('expenses', 'payment_mode')) {
                $table->integer('payment_mode')->nullable()->after('editedBy');
            }
            if (! Schema::hasColumn('expenses', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            }
            if (! Schema::hasColumn('expenses', 'reason')) {
                $table->string('reason', 255)->nullable()->after('deleted_at');
            }
            if (! Schema::hasColumn('expenses', 'labour_id')) {
                $table->integer('labour_id')->nullable()->after('reason');
            }
            if (! Schema::hasColumn('expenses', 'vendor_id')) {
                $table->integer('vendor_id')->nullable()->after('labour_id');
            }
            if (! Schema::hasColumn('expenses', 'is_advance')) {
                $table->integer('is_advance')->nullable()->after('vendor_id');
            }
        });

        $this->copyOldExpenseData();
        $this->fillRequiredIds();
        $this->dropOldForeignKeys();

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('expenses', function (Blueprint $table) {
                foreach ([
                    'expense_code',
                    'employee_id',
                    'title',
                    'type',
                    'category',
                    'status',
                    'expense_date',
                    'paid_amount',
                    'unpaid_amount',
                    'extra_amount',
                    'active_status',
                    'delete_status',
                    'delete_reason',
                    'notes',
                ] as $column) {
                    if (Schema::hasColumn('expenses', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (DB::getDriverName() !== 'sqlite') {
            $this->rebuildAmountColumnAsInteger();
            DB::statement('ALTER TABLE expenses MODIFY category_id INT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE expenses MODIFY user_id INT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE expenses MODIFY `current_date` DATETIME NOT NULL');
            DB::statement('ALTER TABLE expenses MODIFY paid_amt INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE expenses MODIFY unpaid_amt INT NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE expenses MODIFY extra_amt INT NULL');
            DB::statement('ALTER TABLE expenses MODIFY payment_mode INT NULL');
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (! Schema::hasTable('expenses')) {
            return;
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'expense_code')) {
                $table->string('expense_code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('expenses', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('project_id');
            }
            if (! Schema::hasColumn('expenses', 'category')) {
                $table->string('category')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'status')) {
                $table->string('status')->default('pending')->after('category');
            }
            if (! Schema::hasColumn('expenses', 'expense_date')) {
                $table->date('expense_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('expenses', 'paid_amount')) {
                $table->decimal('paid_amount', 14, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'unpaid_amount')) {
                $table->decimal('unpaid_amount', 14, 2)->default(0)->after('paid_amount');
            }
            if (! Schema::hasColumn('expenses', 'extra_amount')) {
                $table->decimal('extra_amount', 14, 2)->default(0)->after('unpaid_amount');
            }
            if (! Schema::hasColumn('expenses', 'delete_status')) {
                $table->boolean('delete_status')->default(false)->after('description');
            }
            if (! Schema::hasColumn('expenses', 'delete_reason')) {
                $table->text('delete_reason')->nullable()->after('delete_status');
            }
        });
    }

    private function copyOldExpenseData(): void
    {
        if (Schema::hasColumn('expenses', 'paid_amount')) {
            DB::statement('UPDATE expenses SET paid_amt = CAST(COALESCE(paid_amount, 0) AS SIGNED)');
        }
        if (Schema::hasColumn('expenses', 'unpaid_amount')) {
            DB::statement('UPDATE expenses SET unpaid_amt = CAST(COALESCE(unpaid_amount, 0) AS SIGNED)');
        }
        if (Schema::hasColumn('expenses', 'extra_amount')) {
            DB::statement('UPDATE expenses SET extra_amt = CAST(COALESCE(extra_amount, 0) AS SIGNED)');
        }
        if (Schema::hasColumn('expenses', 'expense_date')) {
            DB::table('expenses')->whereNull('current_date')->update(['current_date' => now()]);
            DB::statement('UPDATE expenses SET `current_date` = COALESCE(`expense_date`, `created_at`, `current_date`)');
        }
        if (Schema::hasColumn('expenses', 'employee_id')) {
            DB::statement('UPDATE expenses SET user_id = COALESCE(employee_id, user_id)');
        }
        if (Schema::hasColumn('expenses', 'delete_reason')) {
            DB::statement('UPDATE expenses SET reason = delete_reason');
        }
        if (Schema::hasColumn('expenses', 'delete_status')) {
            DB::table('expenses')
                ->where('delete_status', 1)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
            DB::statement('UPDATE expenses SET deleted_at = COALESCE(updated_at, deleted_at) WHERE delete_status = 1');
        }

        if (Schema::hasColumn('expenses', 'category')) {
            $rows = DB::table('expenses')->select('id', 'category')->whereNotNull('category')->get();
            foreach ($rows as $row) {
                $name = trim((string) $row->category);
                if ($name === '') {
                    continue;
                }

                $categoryId = DB::table('categories')->where('name', $name)->value('id');
                if (! $categoryId) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'name' => mb_strtoupper($name),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('expenses')->where('id', $row->id)->update(['category_id' => $categoryId]);
            }
        }

        if (Schema::hasColumn('expenses', 'type')) {
            $rows = DB::table('expenses')->select('id', 'type')->whereNotNull('type')->get();
            foreach ($rows as $row) {
                $name = trim((string) $row->type);
                if ($name === '') {
                    continue;
                }

                $mainCategoryId = DB::table('main_categories')->where('name', $name)->value('id');
                if (! $mainCategoryId) {
                    $mainCategoryId = DB::table('main_categories')->insertGetId([
                        'name' => mb_strtoupper($name),
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('expenses')->where('id', $row->id)->update(['main_category_id' => $mainCategoryId]);
            }
        }
    }

    private function fillRequiredIds(): void
    {
        $mainCategoryId = DB::table('main_categories')->orderBy('id')->value('id');
        if (! $mainCategoryId) {
            $mainCategoryId = DB::table('main_categories')->insertGetId([
                'name' => 'GENERAL',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categoryId = DB::table('categories')->orderBy('id')->value('id');
        if (! $categoryId) {
            $categoryValues = [
                'name' => 'GENERAL',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('categories', 'main_category_id')) {
                $categoryValues['main_category_id'] = $mainCategoryId;
            }

            $categoryId = DB::table('categories')->insertGetId($categoryValues);
        }

        $userId = DB::table('users')->orderBy('id')->value('id');
        if (! $userId) {
            $userId = 1;
        }

        DB::table('expenses')->whereNull('category_id')->update(['category_id' => $categoryId]);
        DB::table('expenses')->whereNull('main_category_id')->update(['main_category_id' => $mainCategoryId]);
        DB::table('expenses')->whereNull('user_id')->update(['user_id' => $userId]);
        DB::table('expenses')->whereNull('current_date')->update(['current_date' => now()]);
    }

    private function rebuildAmountColumnAsInteger(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $type = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'expenses')
            ->where('COLUMN_NAME', 'amount')
            ->value('DATA_TYPE');

        if ($type === 'int') {
            return;
        }

        if (! Schema::hasColumn('expenses', 'amount_int_tmp')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->integer('amount_int_tmp')->default(0)->after('id');
            });
        }

        DB::statement('UPDATE expenses SET amount_int_tmp = CAST(ROUND(COALESCE(amount, 0)) AS SIGNED)');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        DB::statement('ALTER TABLE expenses CHANGE amount_int_tmp amount INT NOT NULL');
    }

    private function dropOldForeignKeys(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'expenses'
              AND REFERENCED_TABLE_NAME IS NOT NULL
              AND COLUMN_NAME IN ('employee_id', 'project_id')
        ");

        foreach ($foreignKeys as $foreignKey) {
            DB::statement('ALTER TABLE expenses DROP FOREIGN KEY `' . $foreignKey->CONSTRAINT_NAME . '`');
        }
    }
};
