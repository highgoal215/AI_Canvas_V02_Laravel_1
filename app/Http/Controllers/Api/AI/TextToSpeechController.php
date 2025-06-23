<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\TextToSpeechService;
use Illuminate\Http\Request;

class TextToSpeechController extends Controller
{
    protected $textToSpeechService;

    public function __construct(TextToSpeechService $textToSpeechService)
    {
        $this->textToSpeechService = $textToSpeechService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:4096',
            'voiceStyle' => 'sometimes|in:alloy,echo,fable,onyx,nova,shimmer',
            'speed' => 'numeric|min:0.25|max:4.0',
            // Optionally add response_format and n if you want to expose them
        ]);

        try {
            $urls = $this->textToSpeechService->generate(
                $request->input('prompt'),
                $request->input('voiceStyle', 'alloy'),
                'tts-1',
                'mp3',
                $request->input('speed', 1.0),
                1, // n is always 1 for now
                $request->user() ? $request->user()->id : null
            );
            return response()->json(['urls' => $urls]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Text-to-speech generation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
