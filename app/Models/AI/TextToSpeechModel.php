<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class TextToSpeechModel extends Model
{
    protected $table = 'text_to_speeches';

    protected $fillable = [
        'user_id',
        'prompt',
        'voice_style',
        'speed',
        'result_url',
        'raw_response',
    ];
}
