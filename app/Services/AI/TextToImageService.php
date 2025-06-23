<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\{Storage, Log};
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;
use App\Models\AI\TextToImageModel;
use App\Jobs\ProcessImageJob; // If using queues

class TextToImageService
{
    public function generate(
        string $prompt,
        ?string $imageStyle = null,
        string $aspectRatio = '1:1',
        int $n = 1,
        string $response_format = 'url',
        ?string $user = null
    ): array {
        // Validate response format
        if (!in_array($response_format, ['url', 'b64_json'])) {
            throw new \InvalidArgumentException('Invalid response format');
        }

        $fullPrompt = $imageStyle 
            ? "{$prompt}, in a {$imageStyle} style." 
            : $prompt;

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
        ];

        if ($user) {
            $options['user'] = $user;
        }

        try {
            $response = OpenAI::images()->create($options);
        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            throw new \RuntimeException('Image generation failed');
        }

        $urls = [];
        foreach ($response->data as $data) {
            if ($response_format === 'url') {
                $resultUrl = $data->url;
            } else {
                // Consider queueing for production
                $resultUrl = $this->storeBase64Image($data->b64_json);
            }

            $urls[] = $resultUrl;

            TextToImageModel::create([
                'user_id' => $user,
                'prompt' => $prompt,
                'image_style' => $imageStyle,
                'aspect_ratio' => $aspectRatio,
                'result_url' => $resultUrl,
            ]);
        }

        return $urls;
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
