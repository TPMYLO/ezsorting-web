<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SortingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_folder_id',
        'source_folder_name',
        'destination_folders',
        'images',
        'total_images',
        'sorted_images',
        'remaining_images',
        'current_image_index',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'destination_folders' => 'array',
        'images' => 'array',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'destination_folders' => '[]',
        'images' => '[]',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
