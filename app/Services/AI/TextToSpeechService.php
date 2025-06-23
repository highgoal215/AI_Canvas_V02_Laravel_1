<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;
use App\Models\AI\TextToSpeechModel;

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
     * @param int|null $userId
     * @return string
     */
    public function generate(string $prompt, string $voiceStyle = 'alloy', string $model = 'tts-1', string $response_format = 'mp3', float $speed = 1.0, ?int $userId = null): string
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
        $resultUrl = Storage::disk('public')->url($filename);

        // Save to database
        TextToSpeechModel::create([
            'user_id' => $userId,
            'prompt' => $prompt,
            'voice_style' => $voiceStyle,
            'speed' => $speed,
            'result_url' => $resultUrl,
            'raw_response' => base64_encode($response),
        ]);

        return $resultUrl;
    }
}
