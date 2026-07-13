<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Casual Leave', 'Sick Leave', 'Annual Leave'] as $name) {
            LeaveType::query()->updateOrCreate(
                ['name' => $name],
                ['status' => 'active']
            );
        }
    }
}
