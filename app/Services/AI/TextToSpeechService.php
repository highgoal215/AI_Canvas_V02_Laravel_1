<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\{Storage, Log};
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
     * @param int $n
     * @param int|null $userId
     * @return array
     */
    public function generate(
        string $prompt,
        ?string $voiceStyle = 'alloy',
        string $model = 'tts-1',
        string $response_format = 'mp3',
        float $speed = 1.0,
        int $n = 1,
        ?int $userId = null
    ): array {
        // Validate response format
        if (!in_array($response_format, ['mp3', 'opus', 'aac', 'flac'])) {
            throw new \InvalidArgumentException('Invalid response format');
        }

        $options = [
            'model' => $model,
            'input' => $prompt,
            'voice' => $voiceStyle,
            'response_format' => $response_format,
            'speed' => $speed,
        ];

        $urls = [];
        for ($i = 0; $i < $n; $i++) {
            try {
                $response = OpenAI::audio()->speech($options);
            } catch (\Exception $e) {
                Log::error('OpenAI TTS API error: ' . $e->getMessage());
                throw new \RuntimeException('Text-to-speech generation failed');
            }

            $filename = 'audio/' . Str::random(40) . '.' . $response_format;
            if (!Storage::disk('public')->put($filename, $response)) {
                throw new \RuntimeException('Failed to store audio');
            }
            $resultUrl = Storage::disk('public')->url($filename);
            $urls[] = $resultUrl;

            TextToSpeechModel::create([
                'user_id' => $userId,
                'prompt' => $prompt,
                'voice_style' => $voiceStyle,
                'speed' => $speed,
                'result_url' => $resultUrl,
                // 'raw_response' => base64_encode($response), // Optionally store raw response
            ]);
        }

        return $urls;
    }
}
