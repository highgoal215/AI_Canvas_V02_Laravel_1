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
     * @param bool $enhancePrompt
     * @return array
     */
    public function Speechgenerate(string $prompt, string $voiceStyle = 'alloy', string $model = 'tts-1', string $response_format = 'mp3', float $speed = 1.0, ?int $userId = null, bool $enhancePrompt = false): string
    {
        try {
            Log::info('TextToSpeechService: Starting speech generation', [
                'prompt_length' => strlen($prompt),
                'voice_style' => $voiceStyle,
                'model' => $model,
                'speed' => $speed,
                'enhance_prompt' => $enhancePrompt,
                'user_id' => $userId
            ]);

            $processedPrompt = $prompt;
            
            // Use GPT-4 to enhance the prompt if requested
            if ($enhancePrompt) {
                $enhancedPrompt = $this->enhanceSpeechPromptWithGPT4($prompt, $voiceStyle);
                $processedPrompt = $enhancedPrompt;
                Log::info('TextToSpeechService: Prompt enhanced with GPT-4', [
                    'original_prompt' => $prompt,
                    'enhanced_prompt' => $enhancedPrompt
                ]);
            }

            $options = [
                'model' => $model,
                'input' => $processedPrompt,
                'voice' => $voiceStyle,
                'response_format' => $response_format,
                'speed' => $speed,
                'timeout' => 60, // Add timeout
            ];

            Log::info('TextToSpeechService: Making OpenAI API request', [
                'options' => array_merge($options, ['input' => substr($processedPrompt, 0, 100) . '...'])
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
                    'enhanced_prompt' => $enhancePrompt ? $processedPrompt : null,
                    'original_prompt' => $prompt,
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
     * Enhance speech prompt using GPT-4
     */
    private function enhanceSpeechPromptWithGPT4(string $prompt, string $voiceStyle): string
    {
        try {
            $systemPrompt = "You are an expert at creating natural, engaging text for speech synthesis. Enhance the given text to be more natural, clear, and suitable for text-to-speech conversion. Make it flow better, add appropriate pauses, and ensure it sounds natural when spoken aloud.";

            $userPrompt = "Text: $prompt\nVoice Style: $voiceStyle\n\nPlease enhance this text for better speech synthesis while maintaining the original meaning.";

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 300,
                'temperature' => 0.5,
            ]);

            $enhancedPrompt = $response->choices[0]->message->content;
            
            Log::info('TextToSpeechService: GPT-4 prompt enhancement completed', [
                'original_prompt' => $prompt,
                'enhanced_prompt' => $enhancedPrompt
            ]);

            return $enhancedPrompt;

        } catch (\Exception $e) {
            Log::warning('TextToSpeechService: GPT-4 prompt enhancement failed, using original prompt', [
                'error' => $e->getMessage(),
                'original_prompt' => $prompt
            ]);
            return $prompt; // Fallback to original prompt
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
