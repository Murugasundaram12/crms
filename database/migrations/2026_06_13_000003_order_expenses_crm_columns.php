<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE expenses MODIFY amount INT NOT NULL AFTER id');
        DB::statement('ALTER TABLE expenses MODIFY main_category_id INT NULL AFTER amount');
        DB::statement('ALTER TABLE expenses MODIFY category_id INT UNSIGNED NOT NULL AFTER main_category_id');
        DB::statement('ALTER TABLE expenses MODIFY project_id INT UNSIGNED NULL AFTER category_id');
        DB::statement('ALTER TABLE expenses MODIFY user_id INT UNSIGNED NOT NULL AFTER project_id');
        DB::statement('ALTER TABLE expenses MODIFY `current_date` DATETIME NOT NULL AFTER user_id');
        DB::statement('ALTER TABLE expenses MODIFY description TEXT NULL AFTER `current_date`');
        DB::statement('ALTER TABLE expenses MODIFY paid_amt INT NOT NULL DEFAULT 0 AFTER description');
        DB::statement('ALTER TABLE expenses MODIFY unpaid_amt INT NOT NULL DEFAULT 0 AFTER paid_amt');
        DB::statement('ALTER TABLE expenses MODIFY extra_amt INT NULL AFTER unpaid_amt');
        DB::statement('ALTER TABLE expenses MODIFY image VARCHAR(250) NULL AFTER extra_amt');
        DB::statement('ALTER TABLE expenses MODIFY editedBy INT NULL AFTER image');
        DB::statement('ALTER TABLE expenses MODIFY payment_mode INT NULL AFTER editedBy');
        DB::statement('ALTER TABLE expenses MODIFY created_at TIMESTAMP NULL DEFAULT NULL AFTER payment_mode');
        DB::statement('ALTER TABLE expenses MODIFY updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at');
        DB::statement('ALTER TABLE expenses MODIFY deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at');
        DB::statement('ALTER TABLE expenses MODIFY reason VARCHAR(255) NULL AFTER deleted_at');
        DB::statement('ALTER TABLE expenses MODIFY labour_id INT NULL AFTER reason');
        DB::statement('ALTER TABLE expenses MODIFY vendor_id INT NULL AFTER labour_id');
        DB::statement('ALTER TABLE expenses MODIFY is_advance INT NULL AFTER vendor_id');
    }

    public function down(): void
    {
        //
    }
};
