<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TextToSpeechModel extends Model
{
    use HasFactory;

    protected $table = 'text_to_speeches';

    protected $fillable = [
        'user_id',
        'prompt',
        'voice_style',
        'speed',
        'result_url',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'speed' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
