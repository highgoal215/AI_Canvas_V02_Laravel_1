<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\TextToImageService;
use Illuminate\Http\Request;

class TextToImageController extends Controller
{
    protected $textToImageService;

    public function __construct(TextToImageService $textToImageService)
    {
        $this->textToImageService = $textToImageService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'imageStyle' => 'sometimes|string|max:100',
            'aspectRatio' => 'sometimes|string|in:1:1,16:9,9:16,4:3,3:4',
        ]);

        try {
            $urls = $this->textToImageService->generate(
                $request->input('prompt'),
                $request->input('imageStyle'),
                $request->input('aspectRatio', '1:1'),
                1, // n is always 1
                'url', // response_format is always url
                $request->user() ? $request->user()->id : null
            );
            return response()->json(['urls' => $urls]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Image generation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
