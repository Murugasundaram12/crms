<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => 'Standard',
            'priority' => 'Medium',
            'status' => 'Pending',
            'due_date' => now()->addDays(7)->toDateString(),
            'estimated_hours' => 8.00,
            'logged_hours' => 0.00,
            'is_important' => false,
        ];
    }
}
