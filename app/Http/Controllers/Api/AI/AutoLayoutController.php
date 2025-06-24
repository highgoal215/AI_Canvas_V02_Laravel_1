<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\AutoLayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AutoLayoutController extends Controller
{
    protected $autoLayoutService;

    public function __construct(AutoLayoutService $autoLayoutService)
    {
        $this->autoLayoutService = $autoLayoutService;
    }

    public function suggest(Request $request): JsonResponse
    {
        try {
            Log::info('AutoLayoutController: Request received', [
                'has_content_type' => $request->has('contentType'),
                'has_content_description' => $request->has('contentDescription'),
                'content_type' => $request->input('contentType'),
                'content_description_length' => strlen($request->input('contentDescription', '')),
                'layout_style' => $request->input('layoutStyle'),
                'user_id' => $request->user()?->id
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'contentType' => 'required|string|max:100',
                'contentDescription' => 'required|string|max:1000',
                'layoutStyle' => 'sometimes|string|max:100|in:modern,minimal,bold',
                'aspectRatio' => 'sometimes|string|in:1:1,16:9,9:16,4:3,3:4',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $contentType = $request->input('contentType');
            $contentDescription = $request->input('contentDescription');
            $layoutStyle = $request->input('layoutStyle', 'modern');
            $aspectRatio = $request->input('aspectRatio', '16:9');
            $userId = $request->user()?->id;

            // Generate layout suggestion
            $layout = $this->autoLayoutService->suggestLayout(
                $contentType,
                $contentDescription,
                $layoutStyle,
                $aspectRatio,
                $userId
            );

            Log::info('AutoLayoutController: Layout suggestion completed', [
                'content_type' => $contentType,
                'layout_style' => $layoutStyle,
                'elements_count' => count($layout['suggestedLayout']['elements']),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Layout suggestion generated successfully',
                'data' => [
                    'layout' => $layout,
                    'content_type' => $contentType,
                    'layout_style' => $layoutStyle,
                    'aspect_ratio' => $aspectRatio,
                    'elements_count' => count($layout['suggestedLayout']['elements'])
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('AutoLayoutController: Layout suggestion failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Provide specific error messages
            $errorMessage = 'Failed to generate layout suggestion';
            if (str_contains($e->getMessage(), 'Content type and description are required')) {
                $errorMessage = 'Content type and description are required for layout generation.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
