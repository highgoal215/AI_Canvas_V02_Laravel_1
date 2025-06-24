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
     * @return array
     */
    public function Imagegenerate(string $prompt, ?string $imageStyle = null, string $aspectRatio = '1:1', int $n = 1, string $response_format = 'url', ?int $userId = null): array
    {
        try {
            Log::info('TextToImageService: Starting image generation', [
                'prompt_length' => strlen($prompt),
                'image_style' => $imageStyle,
                'aspect_ratio' => $aspectRatio,
                'user_id' => $userId
            ]);

            $fullPrompt = $prompt;
            if ($imageStyle) {
                $fullPrompt .= ', in a ' . $imageStyle . ' style.';
            }

            $size = match ($aspectRatio) {
                '16:9' => '1792x1024',
                '9:16' => '1024x1792',
                default => '1024x1024',
            };

            $options = [
                'prompt' => $fullPrompt,
                'n' => $n,
                'size' => $size,
                'response_format' => $response_format,
                'timeout' => 60, // Add timeout
            ];

            if ($userId) {
                $options['user'] = (string) $userId;
            }

            Log::info('TextToImageService: Making OpenAI API request', [
                'options' => array_merge($options, ['prompt' => substr($fullPrompt, 0, 100) . '...'])
            ]);

            $response = OpenAI::images()->create($options);

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
