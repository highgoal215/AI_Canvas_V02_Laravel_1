<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;
use App\Models\AI\TextToSpeechModel;
use Illuminate\Support\Facades\Log;

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
    public function Speechgenerate(string $prompt, string $voiceStyle = 'alloy', string $model = 'tts-1', string $response_format = 'mp3', float $speed = 1.0, ?int $userId = null): string
    {
        try {
            Log::info('TextToSpeechService: Starting speech generation', [
                'prompt_length' => strlen($prompt),
                'voice_style' => $voiceStyle,
                'model' => $model,
                'speed' => $speed,
                'user_id' => $userId
            ]);

            $options = [
                'model' => $model,
                'input' => $prompt,
                'voice' => $voiceStyle,
                'response_format' => $response_format,
                'speed' => $speed,
                'timeout' => 60, // Add timeout
            ];

            Log::info('TextToSpeechService: Making OpenAI API request', [
                'options' => array_merge($options, ['input' => substr($prompt, 0, 100) . '...'])
            ]);

            $response = OpenAI::audio()->speech($options);

            if (!$response) {
                throw new \Exception('Invalid response from OpenAI API');
            }

            Log::info('TextToSpeechService: Received response from OpenAI', [
                'response_size' => strlen($response)
            ]);

            $filename = 'audio/' . Str::random(40) . '.' . $response_format;
            Storage::disk('public')->put($filename, $response);
            $resultUrl = Storage::disk('public')->url($filename);

            // Save to database with metadata instead of large base64 data
            TextToSpeechModel::create([
                'user_id' => $userId,
                'prompt' => $prompt,
                'voice_style' => $voiceStyle,
                'speed' => $speed,
                'result_url' => $resultUrl,
                'raw_response' => [
                    'model' => $model,
                    'response_format' => $response_format,
                    'response_size' => strlen($response),
                    'generated_at' => now()->toISOString(),
                    'filename' => $filename
                ],
            ]);

            Log::info('TextToSpeechService: Speech generation completed successfully', [
                'result_url' => $resultUrl,
                'user_id' => $userId
            ]);

            return $resultUrl;

        } catch (\Exception $e) {
            Log::error('TextToSpeechService: Speech generation failed', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt),
                'voice_style' => $voiceStyle,
                'user_id' => $userId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
}
