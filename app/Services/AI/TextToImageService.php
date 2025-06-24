<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\{Storage, Log};
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;
use App\Models\AI\TextToImageModel;

class TextToImageService
{
    /**
     * @param string $prompt
     * @param string|null $imageStyle
     * @param string $aspectRatio
     * @param int $n
     * @param string $response_format
     * @param int|null $userId
     * @param bool $enhancePrompt
     * @return array
     */
    public function Imagegenerate(string $prompt, ?string $imageStyle = null, string $aspectRatio = '1:1', int $n = 1, string $response_format = 'url', ?int $userId = null, bool $enhancePrompt = false): array
    {
        try {
            Log::info('TextToImageService: Starting image generation', [
                'prompt_length' => strlen($prompt),
                'image_style' => $imageStyle,
                'aspect_ratio' => $aspectRatio,
                'enhance_prompt' => $enhancePrompt,
                'user_id' => $userId
            ]);

            // Debug: Check what API key is being loaded
        $apiKey = config('openai.api_key');
        Log::info('OpenAI API Key Debug', [
            'key_exists' => !empty($apiKey),
            'key_length' => strlen($apiKey ?? ''),
            'key_prefix' => substr($apiKey ?? '', 0, 10),
            'env_key_exists' => !empty(env('OPENAI_API_KEY')),
        ]);
                 // Validate API key exists
            if (!config('openai.api_key')) {
                throw new \Exception('OpenAI API key not configured');
            }
            $fullPrompt = $prompt;
            
            // Use GPT-4.1 to enhance the prompt if requested
            if ($enhancePrompt) {
                $enhancedPrompt = $this->enhancePromptWithGPT4($prompt, $imageStyle);
                $fullPrompt = $enhancedPrompt;
                Log::info('TextToImageService: Prompt enhanced with GPT-4.1', [
                    'original_prompt' => $prompt,
                    'enhanced_prompt' => $enhancedPrompt
                ]);
            } else {
                if ($imageStyle) {
                    $fullPrompt .= ', in a ' . $imageStyle . ' style.';
                }
            }

            $size = match ($aspectRatio) {
                '16:9' => '1792x1024',
                '9:16' => '1024x1792',
                default => '1024x1024',
            };

            $options = [
                'model' => 'dall-e-3',
                'prompt' => $fullPrompt,
                'n' => $n,
                'size' => $size,
                'quality' => 'standard', // Use high quality
                'response_format' => $response_format,
                'timeout' => 60, // Add timeout
            ];

            if ($userId) {
                $options['user'] = (string) $userId;
            }

             Log::info('TextToImageService: Making DALL-E 3 API request', [
                'model' => 'dall-e-3',
                'size' => $size,
                'quality' => $options['quality'],
                'prompt_preview' => substr($fullPrompt, 0, 100) . '...'
            ]);


            $response = OpenAI::images()->create(parameters: $options);

            if (!$response || !isset($response->data)) {
                throw new \Exception('Invalid response from OpenAI API');
            }

            Log::info('TextToImageService: Received response from OpenAI', [
                'data_count' => count($response->data)
            ]);

            $urls = [];
            foreach ($response->data as $data) {
                if ($response_format === 'url' || $response_format === 'uri') {
                    $resultUrl = $data->url;
                } else {
                    $imageContent = base64_decode($data->b64_json);
                    $filename = 'generated-images/' . Str::random(40) . '.png';
                    Storage::disk('public')->put($filename, $imageContent);
                    $resultUrl = Storage::disk('public')->url($filename);
                }
                $urls[] = $resultUrl;

                // Save to database
                TextToImageModel::create([
                    'user_id' => $userId,
                    'prompt' => $prompt,
                    'image_style' => $imageStyle,
                    'aspect_ratio' => $aspectRatio,
                    'result_url' => $resultUrl,
                    'raw_response' => [
                        'response_format' => $response_format,
                        'size' => $size,
                        'quality' => $options['quality'],
                        'enhanced_prompt' => $enhancePrompt,
                        'original_prompt' => $prompt,
                        'generated_at' => now()->toISOString(),
                        'response_data_count' => count($response->data)
                    ],
                ]);
            }

            Log::info('TextToImageService: Image generation completed successfully', [
                'urls_count' => count($urls),
                'user_id' => $userId
            ]);

            return $urls;

        } catch (\Exception $e) {
            Log::error('TextToImageService: Image generation failed', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt),
                'user_id' => $userId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Enhance prompt using GPT-4.1
     */
    private function enhancePromptWithGPT4(string $prompt, ?string $imageStyle = null): string
    {
        try {
            $systemPrompt = "You are an expert at creating detailed, vivid image prompts for AI image generation. Enhance the given prompt to be more descriptive, specific, and visually appealing while maintaining the original intent. Focus on adding visual details, lighting, composition, and artistic elements.";

            $userPrompt = $prompt;
            if ($imageStyle) {
                $userPrompt .= " Style: " . $imageStyle;
            }

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini', // Using GPT-4o-mini (latest available)
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $enhancedPrompt = $response->choices[0]->message->content;
            
            Log::info('TextToImageService: GPT-4 prompt enhancement completed', [
                'original_prompt' => $prompt,
                'enhanced_prompt' => $enhancedPrompt
            ]);

            return $enhancedPrompt;

        } catch (\Exception $e) {
            Log::warning('TextToImageService: GPT-4 prompt enhancement failed, using original prompt', [
                'error' => $e->getMessage(),
                'original_prompt' => $prompt
            ]);
            return $prompt; // Fallback to original prompt
        }
    }

    private function storeBase64Image(string $b64_json): string
    {
        $imageContent = base64_decode($b64_json);
        $filename = 'generated-images/' . Str::random(40) . '.png';
        
        if (!Storage::disk('public')->put($filename, $imageContent)) {
            throw new \RuntimeException('Failed to store image');
        }

        return Storage::disk('public')->url($filename);
    }

    /**
     * Regenerate images with the same parameters as a previous generation.
     *
     * @param int $originalGenerationId The ID of the original generation to regenerate
     * @param int|null $userId The user ID (optional)
     * @return array
     */
    public function ImageRegenerate(int $originalGenerationId, ?int $userId = null): array
    {
        try {
            Log::info('TextToImageService: Starting image regeneration', [
                'original_generation_id' => $originalGenerationId,
                'user_id' => $userId
            ]);

            // Find the original generation
            $originalGeneration = TextToImageModel::find($originalGenerationId);
            
            if (!$originalGeneration) {
                throw new \Exception('Original generation not found');
            }

            // Verify user ownership if userId is provided
            if ($userId && $originalGeneration->user_id !== $userId) {
                throw new \Exception('Unauthorized access to this generation');
            }

            Log::info('TextToImageService: Found original generation', [
                'original_prompt' => $originalGeneration->prompt,
                'original_style' => $originalGeneration->image_style,
                'original_aspect_ratio' => $originalGeneration->aspect_ratio
            ]);

            // Regenerate with the same parameters
            $urls = $this->Imagegenerate(
                $originalGeneration->prompt,
                $originalGeneration->image_style,
                $originalGeneration->aspect_ratio,
                1, // n is always 1
                'url', // response_format is always url
                $userId
            );

            Log::info('TextToImageService: Image regeneration completed successfully', [
                'original_generation_id' => $originalGenerationId,
                'new_urls_count' => count($urls),
                'user_id' => $userId
            ]);

            return $urls;

        } catch (\Exception $e) {
            Log::error('TextToImageService: Image regeneration failed', [
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
