<?php

namespace Database\Factories;

use App\Models\MainCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MainCategory>
 */
class MainCategoryFactory extends Factory
{
    protected $model = MainCategory::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->unique()->word()),
            'status' => 1,
        ];
    }
}
