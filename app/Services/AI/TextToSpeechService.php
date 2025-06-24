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

    /**
     * Regenerate speech with the same parameters as a previous generation.
     *
     * @param int $originalGenerationId The ID of the original generation to regenerate
     * @param int|null $userId The user ID (optional)
     * @return string
     */
    public function SpeechRegenerate(int $originalGenerationId, ?int $userId = null): string
    {
        try {
            Log::info('TextToSpeechService: Starting speech regeneration', [
                'original_generation_id' => $originalGenerationId,
                'user_id' => $userId
            ]);

            // Find the original generation
            $originalGeneration = TextToSpeechModel::find($originalGenerationId);
            
            if (!$originalGeneration) {
                throw new \Exception('Original generation not found');
            }

            // Verify user ownership if userId is provided
            if ($userId && $originalGeneration->user_id !== $userId) {
                throw new \Exception('Unauthorized access to this generation');
            }

            Log::info('TextToSpeechService: Found original generation', [
                'original_text' => substr($originalGeneration->prompt, 0, 100) . '...',
                'original_voice' => $originalGeneration->voice_style,
                'original_speed' => $originalGeneration->speed
            ]);

            // Regenerate with the same parameters
            $resultUrl = $this->Speechgenerate(
                $originalGeneration->prompt,
                $originalGeneration->voice_style,
                'tts-1', // model
                'mp3', // response_format
                $originalGeneration->speed,
                $userId
            );

            Log::info('TextToSpeechService: Speech regeneration completed successfully', [
                'original_generation_id' => $originalGenerationId,
                'result_url' => $resultUrl,
                'user_id' => $userId
            ]);

            return $resultUrl;

        } catch (\Exception $e) {
            Log::error('TextToSpeechService: Speech regeneration failed', [
                'error' => $e->getMessage(),
                'original_generation_id' => $originalGenerationId,
                'user_id' => $userId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
}
