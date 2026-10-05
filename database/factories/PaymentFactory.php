<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStage;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-' . fake()->unique()->numerify('#####'),
            'payment_code' => 'PAY-' . fake()->unique()->numerify('#####'),
            'project_id' => Project::factory(),
            'client_id' => Client::factory(),
            'quotation_id' => \App\Models\Quotation::factory(),
            'stage_id' => PaymentStage::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_method' => 'cash',
            'amount' => 50000.00,
            'due_date' => now()->toDateString(),
            'payment_date' => now(),
            'status' => 'paid',
            'notes' => fake()->sentence(),
        ];
    }
}
