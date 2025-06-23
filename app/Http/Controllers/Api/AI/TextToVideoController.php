<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\TextToVideoService;
use Illuminate\Http\Request;

class TextToVideoController extends Controller
{
    protected $textToVideoService;

    public function __construct(TextToVideoService $textToVideoService)
    {
        $this->textToVideoService = $textToVideoService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'videoStyle' => 'sometimes|string|max:100',
            'duration' => 'sometimes|string|max:20',
        ]);

        $videoUrl = $this->textToVideoService->generate(
            $request->input('prompt'),
            $request->input('videoStyle'),
            $request->input('duration'),
            $request->user() ? $request->user()->id : null
        );

        return response()->json(['url' => $videoUrl]);
    }
}
