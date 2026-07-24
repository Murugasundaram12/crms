<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentMethodMasterFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['expense_transactions', 'payment_methods', 'categories', 'main_categories', 'projects', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('main_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->boolean('active_status')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('expense_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('main_category_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->integer('payment_mode')->nullable();
            $table->date('current_date')->nullable();
            $table->string('current_time')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('active_status')->default(true);
            $table->boolean('delete_status')->default(false);
            $table->timestamps();
        });
    }

    public function test_expense_transaction_accepts_payment_method_master_id(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);

        $mainCategoryId = DB::table('main_categories')->insertGetId([
            'name' => 'Operations',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Fuel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectId = DB::table('projects')->insertGetId([
            'name' => 'Project A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentMethod = PaymentMethod::create([
            'name' => 'CASH',
            'code' => 'CASH',
            'active_status' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('expense-transactions.store'), [
                'main_category_id' => $mainCategoryId,
                'category_id' => $categoryId,
                'project_id' => $projectId,
                'description' => 'Fuel expense',
                'paid_amount' => 250,
                'payment_mode' => $paymentMethod->id,
                'current_date' => '01/02/2026',
                'current_time' => '10:00:00 AM',
            ])
            ->assertRedirect(route('expense-transactions.index'));

        $this->assertDatabaseHas('expense_transactions', [
            'payment_mode' => $paymentMethod->id,
            'user_id' => $user->id,
        ]);
    }
}
