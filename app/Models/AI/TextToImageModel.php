<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TextToImageModel extends Model
{
    use HasFactory;

    protected $table = 'text_to_images';

    protected $fillable = [
        'user_id',
        'prompt',
        'image_style',
        'aspect_ratio',
        'result_url',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
    ];

    protected $hidden = [
        'raw_response',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
