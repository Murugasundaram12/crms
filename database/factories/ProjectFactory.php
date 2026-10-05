<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_code' => 'PRJ-' . fake()->unique()->numerify('#####'),
            'client_id' => Client::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => 'Residential',
            'priority' => 'Medium',
            'status' => 'Ongoing',
            'progress' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'location' => fake()->address(),
            'advance_amt' => 10000.00,
            'profit' => 0.00,
        ];
    }
}
