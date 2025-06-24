<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\{Storage, Log};
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;
use App\Models\AI\TextToImageModel;
use Illuminate\Support\Facades\Log;

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
}
