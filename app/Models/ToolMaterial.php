<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolMaterial extends Model
{
    protected $table = 'tools_materials';

    protected $fillable = [
        'name',
        'image_path',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ToolMaterialAssignment::class);
    }
}
