<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

class TextToSpeechService
{
    /**
     * Generate audio from text using OpenAI TTS.
     *
     * @param string $prompt
     * @param string $voiceStyle
     * @param string $model
     * @param string $response_format
     * @param float $speed
     * @return string
     */
    public function generate(string $prompt, string $voiceStyle = 'alloy', string $model = 'tts-1', string $response_format = 'mp3', float $speed = 1.0): string
    {
        $options = [
            'model' => $model,
            'input' => $prompt,
            'voice' => $voiceStyle,
            'response_format' => $response_format,
            'speed' => $speed,
        ];

        $response = OpenAI::audio()->speech($options);

        $filename = 'audio/' . Str::random(40) . '.' . $response_format;
        Storage::disk('public')->put($filename, $response);

        return Storage::disk('public')->url($filename);
    }
}
