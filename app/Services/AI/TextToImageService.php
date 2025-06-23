<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Storage;
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
     * @param string|null $user
     * @return array
     */
    public function generate(string $prompt, ?string $imageStyle = null, string $aspectRatio = '1:1', int $n = 1, string $response_format = 'url', ?string $user = null): array
    {
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
        ];

        if ($user) {
            $options['user'] = $user;
        }

        $response = OpenAI::images()->create($options);

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
                'user_id' => $user,
                'prompt' => $prompt,
                'image_style' => $imageStyle,
                'aspect_ratio' => $aspectRatio,
                'result_url' => $resultUrl,
            ]);
        }

        return $urls;
    }
}
