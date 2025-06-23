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
        ]);

        $audioUrl = $this->textToSpeechService->generate(
            $request->input('prompt'),
            $request->input('voiceStyle', 'alloy'),
            'tts-1',
            'mp3',
            $request->input('speed', 1.0),
            $request->user() ? $request->user()->id : null
        );

        return response()->json(['url' => $audioUrl]);
    }
}
