<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MainCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->unique()->word()),
            'main_category_id' => MainCategory::factory(),
        ];
    }
}
