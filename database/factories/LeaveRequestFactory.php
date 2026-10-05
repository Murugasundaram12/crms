<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'leave_type_id' => 1,
            'from_date' => now()->addDays(2)->toDateString(),
            'to_date' => now()->addDays(3)->toDateString(),
            'remarks' => fake()->sentence(),
            'status' => 'pending',
            'created_by_id' => User::factory(),
        ];
    }
}
