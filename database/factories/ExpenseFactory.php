<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MainCategory;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'amount' => 1500.00,
            'paid_amt' => 1500.00,
            'unpaid_amt' => 0.00,
            'extra_amt' => 0.00,
            'main_category_id' => MainCategory::factory(),
            'category_id' => Category::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'current_date' => now(),
            'description' => fake()->sentence(),
            'is_advance' => 0,
        ];
    }
}
