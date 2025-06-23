<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class TextToVideoModel extends Model
{
    protected $table = 'text_to_videos';

    protected $fillable = [
        'user_id',
        'prompt',
        'video_style',
        'duration',
        'result_url',
        'raw_response',
    ];
}
