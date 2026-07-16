<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolMaterialAssignment extends Model
{
    protected $fillable = [
        'tool_material_id',
        'from_project_id',
        'to_project_id',
        'transfer_type',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function toolMaterial(): BelongsTo
    {
        return $this->belongsTo(ToolMaterial::class);
    }

    public function fromProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'from_project_id');
    }

    public function toProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'to_project_id');
    }
}
