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
            'n' => 'integer|min:1|max:10',
            'response_format' => 'in:url,b64_json',
        ]);

        $urls = $this->textToImageService->generate(
            $request->input('prompt'),
            $request->input('imageStyle'),
            $request->input('aspectRatio', '1:1'),
            $request->input('n', 1),
            $request->input('response_format', 'url'),
            $request->user()->id
        );

        return response()->json(['urls' => $urls]);
    }
}
