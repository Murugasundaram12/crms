<?php

namespace Database\Factories;

use App\Models\Labour;
use App\Models\LabourRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Labour>
 */
class LabourFactory extends Factory
{
    protected $model = Labour::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'phone_number' => fake()->phoneNumber(),
            'labour_role_id' => LabourRole::factory(),
            'gender' => 'male',
            'salary' => 850.00,
            'advance_amt' => 0.00,
        ];
    }
}
