<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? Str::upper($value) : $value,
            set: fn(?string $value) => $value ? Str::upper(trim($value)) : $value,
        );
    }

    public function mainCategories()
    {
        return $this->belongsToMany(MainCategory::class, 'category_main_category')
            ->withTimestamps();
    }
}
