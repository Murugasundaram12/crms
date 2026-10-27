<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertNotProtectedDatabase();

        if (config('database.default') === 'sqlite' && ! \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $this->ensureBasicTestSchema();
        }
    }

    protected function assertNotProtectedDatabase(): void
    {
        $defaultConn = config('database.default');
        $dbName = config("database.connections.{$defaultConn}.database");

        if ($defaultConn === 'mysql' && in_array(strtolower((string) $dbName), ['admin_crms', 'crms'], true)) {
            throw new \RuntimeException(
                "CRITICAL SAFETY VIOLATION: Automated tests are blocked from running against the protected database ({$dbName})!"
            );
        }
    }

    protected function ensureBasicTestSchema(): void
    {
        $schema = \Illuminate\Support\Facades\Schema::connection('sqlite');

        if (! $schema->hasTable('roles')) {
            $schema->create('roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('permissions')) {
            $schema->create('permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('role_permission')) {
            $schema->create('role_permission', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('role_id');
                $table->foreignId('permission_id');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('model_has_permissions')) {
            $schema->create('model_has_permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        if (! $schema->hasTable('model_has_roles')) {
            $schema->create('model_has_roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (! $schema->hasTable('role_has_permissions')) {
            $schema->create('role_has_permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (! $schema->hasTable('users')) {
            $schema->create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('phone')->nullable();
                $table->string('designation')->nullable();
                $table->string('address')->nullable();
                $table->date('hire_date')->nullable();
                $table->decimal('hourly_rate', 12, 2)->nullable();
                $table->string('avatar')->nullable();
                $table->string('role')->nullable();
                $table->string('salary_name')->nullable();
                $table->decimal('salary_amount', 14, 2)->nullable()->default(0);
                $table->string('salary_type')->nullable();
                $table->decimal('wallet', 14, 2)->default(0);
                $table->string('status')->default('active');
                $table->boolean('is_employee')->default(false);
                $table->string('password')->default('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('user_roles')) {
            $schema->create('user_roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('role_id');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('password_reset_tokens')) {
            $schema->create('password_reset_tokens', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! $schema->hasTable('employees')) {
            $schema->create('employees', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('designation')->nullable();
                $table->string('role')->nullable();
                $table->string('address')->nullable();
                $table->decimal('hourly_rate', 12, 2)->default(0);
                $table->date('hire_date')->nullable();
                $table->string('status')->default('active');
                $table->string('avatar')->nullable();
                $table->string('salary_name')->nullable();
                $table->decimal('salary_amount', 14, 2)->nullable()->default(0);
                $table->string('salary_type')->nullable();
                $table->string('password')->nullable();
                $table->decimal('wallet', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('attendances')) {
            $schema->create('attendances', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->date('date')->nullable();
                $table->date('attendance_date')->nullable();
                $table->timestamp('check_in_at')->nullable();
                $table->timestamp('check_out_at')->nullable();
                $table->unsignedInteger('worked_minutes')->nullable();
                $table->string('status')->default('present');
                $table->time('check_in')->nullable();
                $table->time('check_out')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('location_trackings')) {
            $schema->create('location_trackings', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('attendance_id')->nullable();
                $table->foreignId('employee_id')->nullable();
                $table->string('device_id')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('accuracy', 8, 2)->nullable();
                $table->decimal('speed', 8, 2)->nullable();
                $table->string('activity')->nullable();
                $table->boolean('is_gps_on')->default(true);
                $table->boolean('is_mock_location')->default(false);
                $table->unsignedTinyInteger('battery_percentage')->nullable();
                $table->string('type')->default('travelling');
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('employee_devices')) {
            $schema->create('employee_devices', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->string('device_id')->nullable();
                $table->string('device_name')->nullable();
                $table->string('device_type')->nullable();
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->text('messaging_token')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('mobile_api_tokens')) {
            $schema->create('mobile_api_tokens', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->string('name')->default('mobile');
                $table->string('token_hash', 64)->unique();
                $table->string('device_id')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('payment_methods')) {
            $schema->create('payment_methods', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('type')->default('cash');
                $table->boolean('active_status')->default(true);
                $table->integer('sort_order')->default(1);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_roles')) {
            $schema->create('labour_roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('salary_type')->default('daily');
                $table->decimal('salary', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labours')) {
            $schema->create('labours', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('job_title')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('gender')->nullable();
                $table->unsignedBigInteger('labour_role_id')->nullable();
                $table->decimal('salary', 14, 2)->default(0);
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_salaries')) {
            $schema->create('labour_salaries', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id');
                $table->date('salary_period_start')->nullable();
                $table->date('salary_period_end')->nullable();
                $table->decimal('salary_amount', 14, 2)->default(0);
                $table->decimal('advance_adjusted', 14, 2)->default(0);
                $table->tinyInteger('advance_paid')->default(0);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('remaining_amount', 14, 2)->default(0);
                $table->date('payment_date')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('paid_by')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('wallet')) {
            $schema->create('wallet', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->unsignedBigInteger('client_id')->default(0);
                $table->unsignedBigInteger('project_id')->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->integer('payment_mode')->default(1);
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->tinyInteger('transfer_type')->default(0);
                $table->integer('stage_id')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('current_date')->nullable();
                $table->tinyInteger('active_status')->default(1);
                $table->tinyInteger('delete_status')->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('transferdetails')) {
            $schema->create('transferdetails', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->unsignedBigInteger('labour_id')->nullable();
                $table->string('transfer_type')->default('employee');
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('payment_mode')->default('Cash');
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->text('description')->nullable();
                $table->date('current_date')->nullable();
                $table->string('current_time')->nullable();
                $table->boolean('active_status')->default(true);
                $table->boolean('delete_status')->default(false);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('expenses')) {
            $schema->create('expenses', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('expense_name')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->foreignId('project_id')->nullable();
                $table->foreignId('main_category_id')->nullable();
                $table->foreignId('category_id')->nullable();
                $table->foreignId('labour_id')->nullable();
                $table->foreignId('vendor_id')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('paid_amt', 14, 2)->default(0);
                $table->decimal('unpaid_amt', 14, 2)->default(0);
                $table->decimal('extra_amt', 14, 2)->default(0);
                $table->date('current_date')->nullable();
                $table->tinyInteger('is_advance')->nullable()->default(0);
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->foreignId('editedBy')->nullable();
                $table->string('reason')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('advance_history')) {
            $schema->create('advance_history', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id')->nullable();
                $table->foreignId('vendor_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('labour_expense_transaction_id')->nullable();
                $table->unsignedBigInteger('labour_salary_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('entry_type')->default('credit');
                $table->foreignId('payment_method_id')->nullable();
                $table->text('notes')->nullable();
                $table->date('current_date')->nullable();
                $table->string('current_time')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_salaries')) {
            $schema->create('labour_salaries', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id');
                $table->date('salary_period_start')->nullable();
                $table->date('salary_period_end')->nullable();
                $table->decimal('salary_amount', 14, 2)->default(0);
                $table->decimal('advance_adjusted', 14, 2)->default(0);
                $table->tinyInteger('advance_paid')->default(0);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('remaining_amount', 14, 2)->default(0);
                $table->date('payment_date')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('paid_by')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_wallet_transactions')) {
            $schema->create('labour_wallet_transactions', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id');
                $table->foreignId('employee_id');
                $table->string('type');
                $table->decimal('amount', 14, 2)->default(0);
                $table->foreignId('payment_method_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->date('current_date')->nullable();
                $table->string('current_time')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_wallet_allocations')) {
            $schema->create('labour_wallet_allocations', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('reversal_transaction_id');
                $table->foreignId('credit_transaction_id');
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('leave_types')) {
            $schema->create('leave_types', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('leave_requests')) {
            $schema->create('leave_requests', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->nullable();
                $table->unsignedInteger('leave_type_id')->nullable();
                $table->date('from_date')->nullable();
                $table->date('to_date')->nullable();
                $table->text('document')->nullable();
                $table->text('remarks')->nullable();
                $table->string('status')->default('pending');
                $table->unsignedInteger('created_by_id')->nullable();
                $table->unsignedInteger('approved_by_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('approver_remarks')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('clients')) {
            $schema->create('clients', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('projects')) {
            $schema->create('projects', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('project_code')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->text('description')->nullable();
                $table->string('type')->nullable();
                $table->string('priority')->nullable();
                $table->string('status')->default('planning');
                $table->integer('progress')->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('location')->nullable();
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->decimal('profit', 14, 2)->default(0);
                $table->decimal('budget', 14, 2)->default(0);
                $table->decimal('spent', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('tasks')) {
            $schema->create('tasks', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->nullable();
                $table->foreignId('employee_id')->nullable();
                $table->string('name')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('type')->default('general');
                $table->boolean('auto_repeat')->default(false);
                $table->foreignId('recurring_source_id')->nullable();
                $table->string('priority')->default('Medium');
                $table->string('status')->default('Pending');
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('estimated_hours', 8, 2)->default(0);
                $table->decimal('logged_hours', 8, 2)->default(0);
                $table->boolean('is_important')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('vendors')) {
            $schema->create('vendors', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->boolean('active_status')->default(true);
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('payment_stages')) {
            $schema->create('payment_stages', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->nullable();
                $table->string('stage_name');
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('variations')) {
            $schema->create('variations', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->nullable();
                $table->string('variation_title')->nullable();
                $table->string('type')->default('additional');
                $table->decimal('amount', 14, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('payment_method_id')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('quotations')) {
            $schema->create('quotations', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('quotation_number')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->foreignId('project_id')->nullable();
                $table->timestamp('quotation_date')->nullable();
                $table->string('quotation_title')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->decimal('sub_total', 14, 2)->default(0);
                $table->decimal('gst_percent', 8, 2)->default(0);
                $table->decimal('discount_percent', 8, 2)->default(0);
                $table->string('status')->default('Pending');
                $table->integer('validity_days')->default(30);
                $table->integer('duration_days')->default(30);
                $table->timestamp('start_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('quotation_items')) {
            $schema->create('quotation_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
                $table->string('main_title')->nullable();
                $table->unsignedInteger('main_title_order')->default(0);
                $table->unsignedInteger('item_order')->default(0);
                $table->text('description')->nullable();
                $table->decimal('nos', 10, 2)->nullable();
                $table->decimal('length', 10, 2)->nullable();
                $table->decimal('breadth', 10, 2)->nullable();
                $table->decimal('depth', 10, 2)->nullable();
                $table->decimal('quantity', 10, 2)->default(0);
                $table->string('unit', 50)->nullable();
                $table->decimal('price', 14, 2)->nullable();
                $table->decimal('rate', 14, 2)->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('payments')) {
            $schema->create('payments', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('payment_code')->nullable();
                $table->foreignId('client_id')->nullable();
                $table->foreignId('project_id')->nullable();
                $table->foreignId('quotation_id')->nullable();
                $table->foreignId('stage_id')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('method')->default('cash');
                $table->string('status')->default('pending');
                $table->date('payment_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('employee_salaries')) {
            $schema->create('employee_salaries', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->string('name')->nullable();
                $table->decimal('salary', 14, 2)->default(0);
                $table->string('salary_type')->default('monthly');
                $table->string('salary_period')->nullable();
                $table->decimal('salary_amount', 14, 2)->default(0);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('remaining_amount', 14, 2)->default(0);
                $table->date('payment_date')->nullable();
                $table->foreignId('payment_method_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('paid_by')->nullable();
                $table->decimal('monthly_salary', 14, 2)->default(0);
                $table->unsignedInteger('working_days')->default(0);
                $table->unsignedInteger('present_days')->default(0);
                $table->unsignedInteger('half_days')->default(0);
                $table->unsignedInteger('paid_leave_days')->default(0);
                $table->unsignedInteger('unpaid_leave_days')->default(0);
                $table->unsignedInteger('absent_days')->default(0);
                $table->decimal('per_day_salary', 14, 2)->default(0);
                $table->decimal('gross_salary', 14, 2)->default(0);
                $table->decimal('half_day_deduction', 14, 2)->default(0);
                $table->decimal('unpaid_leave_deduction', 14, 2)->default(0);
                $table->decimal('absent_deduction', 14, 2)->default(0);
                $table->decimal('attendance_deduction', 14, 2)->default(0);
                $table->decimal('other_deductions', 14, 2)->default(0);
                $table->decimal('overtime_amount', 14, 2)->default(0);
                $table->decimal('net_salary', 14, 2)->default(0);
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_assignments')) {
            $schema->create('labour_assignments', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id');
                $table->foreignId('project_id');
                $table->foreignId('employee_id')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('labour_attendances')) {
            $schema->create('labour_attendances', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('labour_id');
                $table->foreignId('employee_id')->nullable();
                $table->foreignId('project_id')->nullable();
                $table->foreignId('labour_salary_id')->nullable();
                $table->date('attendance_date');
                $table->string('status')->default('present');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('main_categories')) {
            $schema->create('main_categories', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('categories')) {
            $schema->create('categories', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->foreignId('main_category_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });
        }
    }
}
