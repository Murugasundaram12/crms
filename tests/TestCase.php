<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite' && ! \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            $this->ensureBasicTestSchema();
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
                $table->string('role')->nullable();
                $table->decimal('wallet', 14, 2)->default(0);
                $table->string('status')->default('active');
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

        if (! $schema->hasTable('employees')) {
            $schema->create('employees', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('wallet', 14, 2)->default(0);
                $table->string('designation')->nullable();
                $table->string('role')->nullable();
                $table->string('status')->default('active');
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
                $table->string('status')->default('present');
                $table->time('check_in')->nullable();
                $table->time('check_out')->nullable();
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
                $table->string('phone')->nullable();
                $table->string('phone_number')->nullable();
                $table->unsignedBigInteger('labour_role_id')->nullable();
                $table->decimal('salary', 14, 2)->default(0);
                $table->decimal('advance_amt', 14, 2)->default(0);
                $table->softDeletes();
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
                $table->tinyInteger('is_advance')->default(0);
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
                $table->text('remarks')->nullable();
                $table->string('status')->default('pending');
                $table->unsignedInteger('created_by_id')->nullable();
                $table->timestamps();
            });
        }
    }
}
