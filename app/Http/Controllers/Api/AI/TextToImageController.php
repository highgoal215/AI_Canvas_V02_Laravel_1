<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\TextToImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TextToImageController extends Controller
{
    protected $textToImageService;

    public function __construct(TextToImageService $textToImageService)
    {
        $this->textToImageService = $textToImageService;
    }

    public function Imagegenerate(Request $request): JsonResponse
    {
        try {
            Log::info('TextToImageController: Request received', [
                'has_prompt' => $request->has('prompt'),
                'prompt_length' => strlen($request->input('prompt', '')),
                'user_id' => $request->user()?->id
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'prompt' => 'required|string|max:1000',
                'imageStyle' => 'sometimes|string|max:100',
                'aspectRatio' => 'sometimes|string|in:1:1,16:9,9:16,4:3,3:4',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $prompt = $request->input('prompt');
            $imageStyle = $request->input('imageStyle');
            $aspectRatio = $request->input('aspectRatio', '1:1');
            $userId = $request->user()?->id;

            // Generate images
            $imageUrls = $this->textToImageService->Imagegenerate(
                $prompt,
                $imageStyle,
                $aspectRatio,
                1, // n is always 1
                'url', // response_format is always url
                $userId
            );

            Log::info('TextToImageController: Image generation completed', [
                'urls_count' => count($imageUrls),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Images generated successfully',
                'data' => [
                    'images' => $imageUrls,
                    'count' => count($imageUrls),
                    'aspect_ratio' => $aspectRatio,
                    'image_style' => $imageStyle
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('TextToImageController: Image generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Provide specific error messages
            $errorMessage = 'Failed to generate images';
            if (str_contains($e->getMessage(), 'Invalid response from OpenAI')) {
                $errorMessage = 'Image generation service is temporarily unavailable. Please try again later.';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'The request timed out. Please try with a shorter prompt.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
