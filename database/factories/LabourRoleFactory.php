<?php

namespace Database\Factories;

use App\Models\LabourRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabourRole>
 */
class LabourRoleFactory extends Factory
{
    protected $model = LabourRole::class;

    public function definition(): array
    {
        return [
            'name' => fake()->jobTitle(),
            'salary_type' => 'daily',
            'salary' => 850.00,
        ];
    }
}
