<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceToTextModel extends Model
{
    protected $table = 'voice_to_texts';

    protected $fillable = [
        'user_id',
        'file_name',
        'transcript',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the transcription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the transcript length.
     */
    public function getTranscriptLengthAttribute(): int
    {
        return strlen($this->transcript);
    }

    /**
     * Get the file size from raw response.
     */
    public function getFileSizeAttribute(): ?int
    {
        return $this->raw_response['file_size'] ?? null;
    }

    /**
     * Get the model used for transcription.
     */
    public function getModelAttribute(): ?string
    {
        return $this->raw_response['model'] ?? null;
    }

    /**
     * Get the response format used.
     */
    public function getResponseFormatAttribute(): ?string
    {
        return $this->raw_response['response_format'] ?? null;
    }
}
