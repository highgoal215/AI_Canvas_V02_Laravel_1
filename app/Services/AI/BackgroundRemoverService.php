<?php

namespace App\Services\AI;

use App\Models\AI\BackgroundRemoverModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Log;

class BackgroundRemoverService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.remove_bg.api_key');
        $this->apiUrl = config('services.remove_bg.api_url');
        
        // Log configuration for debugging
        Log::info('BackgroundRemoverService initialized', [
            'api_key_exists' => !empty($this->apiKey),
            'api_key_length' => strlen($this->apiKey),
            'api_url' => $this->apiUrl
        ]);
    }

    public function removeBackground(UploadedFile $image, string $outputFormat = 'png', ?int $userId = null): string
    {
        try {
            // Prepare the API request with increased timeout and better configuration
            $response = Http::timeout(120) // Increase timeout to 120 seconds for large images
                ->withOptions([
                    'http_errors' => false,
                    'verify' => false, // Disable SSL verification if needed
                    'max_redirects' => 5,
                ])
                ->withHeaders([
                    'X-Api-Key' => $this->apiKey,
                    'Accept' => 'application/json, image/*',
                ])
                ->attach(
                    name: 'image_file', 
                    contents: file_get_contents(filename: $image->getPathname()), 
                    filename: $image->getClientOriginalName()
                )
                ->post($this->apiUrl, data: [
                    'size' => 'auto',
                    'format' => $outputFormat,
                ]);

            if (!$response->successful()) {
                Log::error('Remove.bg API failed', [
                    'status_code' => $response->status(),
                    'response_size' => strlen($response->body()),
                    'headers' => $response->headers()
                ]);
                
                // Only log a small portion of the response body for debugging
                $responseBody = $response->body();
                $bodyPreview = strlen($responseBody) > 500 ? substr($responseBody, 0, 500) . '...' : $responseBody;
                Log::error('Remove.bg API response body preview', ['body_preview' => $bodyPreview]);
                
                throw new \Exception('Background removal API request failed. Status: ' . $response->status());
            }
            
            // Get the processed image data
            $imageData = $response->body();
            
            // Validate that we received image data
            if (empty($imageData)) {
                throw new \Exception('No image data received from API');
            }
            
            // Validate that the response is actually an image
            $contentType = $response->header('Content-Type');
            if ($contentType && !str_contains($contentType, 'image/')) {
                Log::warning('Unexpected content type received', ['content_type' => $contentType]);
            }
            
            // Generate filename and store the processed image
            $filename = 'background-removed/' . Str::random(20) . '.' . $outputFormat;
            Storage::disk('public')->put($filename, $imageData);
            $resultUrl = Storage::disk('public')->url($filename);

            // Store original image for reference
            $originalFilename = 'originals/' . Str::random(40) . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->put($originalFilename, file_get_contents($image->getPathname()));
            $originalUrl = Storage::disk('public')->url($originalFilename);

            // Save to database
            BackgroundRemoverModel::create([
                'user_id' => $userId,
                'original_url' => $originalUrl,
                'result_url' => $resultUrl,
                'raw_response' => [
                    'api_response_size' => strlen($imageData),
                    'status' => 'success',
                    'content_type' => $contentType,
                    'output_format' => $outputFormat,
                    'file_size' => $image->getSize(),
                    'original_filename' => $image->getClientOriginalName(),
                ],
            ]);

            Log::info('Background removal completed successfully', [
                'result_url' => $resultUrl,
                'original_url' => $originalUrl,
                'response_size' => strlen($imageData),
                'content_type' => $contentType
            ]);

            return $resultUrl;
            
        } catch (\Exception $e) {
            Log::error('Background removal service error', [
                'error' => $e->getMessage(),
                'file_size' => $image->getSize(),
                'output_format' => $outputFormat,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
}