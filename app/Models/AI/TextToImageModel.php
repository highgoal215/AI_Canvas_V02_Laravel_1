<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class TextToImageModel extends Model
{
    protected $table = 'text_to_images';

    protected $fillable = [
        'user_id',
        'prompt',
        'image_style',
        'aspect_ratio',
        'result_url',
    ];

    protected $hidden = [
        'raw_response',
    ];
}
