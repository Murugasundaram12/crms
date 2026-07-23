<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreorderDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'preorder_id',
        'document_type',
        'title',
        'file_path',
        'file_size',
        'uploaded_by',
    ];

    public function preorder(): BelongsTo
    {
        return $this->belongsTo(Preorder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
