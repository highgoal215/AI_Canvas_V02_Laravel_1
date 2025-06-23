<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BackgroundRemoverService
{
    /**
     * Removes the background from an image using an external API (e.g., remove.bg).
     *
     * @param string $imageUrl The public URL of the image to process.
     * @return string The public URL of the image with the background removed.
     * @throws \Exception
     */
    public function remove(string $imageUrl): string
    {
        $apiKey = config('services.remove_bg.api_key');

        if (!$apiKey) {
            throw new \Exception('Remove.bg API key is not configured.');
        }

        $response = Http::withHeaders([
            'X-Api-Key' => $apiKey,
        ])->asForm()->post('https://api.remove.bg/v1.0/removebg', [
            'image_url' => $imageUrl,
            'size' => 'auto'
        ]);

        if ($response->successful()) {
            $filename = 'background-removed/' . Str::random(40) . '.png';
            Storage::disk('public')->put($filename, $response->body());
            return Storage::disk('public')->url($filename);
        }

        throw new \Exception('Failed to remove background from image: ' . $response->body());
    }
}
