<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\BackgroundRemoverService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BackgroundRemoverController extends Controller
{
    protected $backgroundRemoverService;

    public function __construct(BackgroundRemoverService $backgroundRemoverService)
    {
        $this->backgroundRemoverService = $backgroundRemoverService;
    }

    public function Backgroundremove(Request $request): JsonResponse
    {
        try {
            // Debug: Log what's being received
            \Log::info('Background removal request received', [
                'has_file' => $request->hasFile('image'),
                'file_size' => $request->file('image')?->getSize(),
                'content_type' => $request->header('Content-Type')
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'image' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max, added webp
                'output_format' => 'sometimes|string|in:png,jpg,jpeg',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $outputFormat = $request->input('output_format', 'png');
            $userId = auth()->id();

            // Process background removal
            $resultUrl = $this->backgroundRemoverService->removeBackground(
                $request->file('image'),
                $outputFormat,
                $userId
            );

            return response()->json([
                'success' => true,
                'message' => 'Background removed successfully',
                'data' => [
                    'result_url' => $resultUrl,
                    'output_format' => $outputFormat
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Background removal error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Provide more specific error messages
            $errorMessage = 'Failed to remove background';
            if (str_contains($e->getMessage(), 'API request failed')) {
                $errorMessage = 'Background removal service is temporarily unavailable. Please try again later.';
            } elseif (str_contains($e->getMessage(), 'No image data received')) {
                $errorMessage = 'The image could not be processed. Please try with a different image.';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'The request timed out. Please try with a smaller image.';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}