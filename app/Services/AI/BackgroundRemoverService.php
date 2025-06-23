<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\AI\BackgroundRemoverModel;

class BackgroundRemoverService
{
    /**
     * Removes the background from an image using an external API (e.g., remove.bg).
     *
     * @param string $imageUrl The public URL of the image to process.
     * @param int|null $userId The user ID (optional).
     * @return string The public URL of the image with the background removed.
     * @throws \Exception
     */
    public function remove(string $imageUrl, ?int $userId = null): string
    {
        $apiKey = config('services.remove_bg.api_key');

        if (!$apiKey) {
            throw new \Exception('Remove.bg API key is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $apiKey,
            ])->asForm()->post('https://api.remove.bg/v1.0/removebg', [
                'image_url' => $imageUrl,
                'size' => 'auto'
            ]);

            if ($response->successful()) {
                $filename = 'background-removed/' . Str::random(40) . '.png';
                Storage::disk('public')->put($filename, $response->body());
                $resultUrl = Storage::disk('public')->url($filename);

                // Save to database
                BackgroundRemoverModel::create([
                    'user_id' => $userId,
                    'original_url' => $imageUrl,
                    'result_url' => $resultUrl,
                    'raw_response' => $response->body(),
                ]);

                return $resultUrl;
            }

            \Illuminate\Support\Facades\Log::error('Remove.bg API error: ' . $response->body());
            throw new \RuntimeException('Failed to remove background from image');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('BackgroundRemoverService error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Background removal failed: ' . $e->getMessage());
        }
    }
}
