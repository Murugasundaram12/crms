<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        return [
            'quotation_number' => 'QUO-' . fake()->unique()->numerify('#####'),
            'client_id' => Client::factory(),
            'project_id' => Project::factory(),
            'quotation_date' => now()->toDateString(),
            'quotation_title' => fake()->sentence(3),
            'amount' => 100000.00,
            'total_amount' => 118000.00,
            'sub_total' => 100000.00,
            'gst_percent' => 18.00,
            'discount_percent' => 0.00,
            'status' => 'Pending',
            'validity_days' => 30,
            'duration_days' => 90,
            'start_date' => now()->toDateString(),
            'notes' => fake()->paragraph(),
        ];
    }
}
