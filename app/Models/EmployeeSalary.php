<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'salary',
        'salary_type',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];
}
