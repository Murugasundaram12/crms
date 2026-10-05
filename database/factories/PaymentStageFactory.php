<?php

namespace Database\Factories;

use App\Models\PaymentStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentStage>
 */
class PaymentStageFactory extends Factory
{
    protected $model = PaymentStage::class;

    public function definition(): array
    {
        return [
            'stage_name' => fake()->unique()->words(2, true),
            'project_id' => \App\Models\Project::factory(),
        ];
    }
}
