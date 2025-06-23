<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;

class VoiceToTextModel extends Model
{
    protected $table = 'voice_to_texts';

    protected $fillable = [
        'user_id',
        'file_name',
        'transcript',
        'raw_response',
    ];
}
