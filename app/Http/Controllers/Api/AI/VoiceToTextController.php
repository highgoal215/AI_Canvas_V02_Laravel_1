<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\VoiceToTextService;
use Illuminate\Http\Request;

class VoiceToTextController extends Controller
{
    protected $voiceToTextService;

    public function __construct(VoiceToTextService $voiceToTextService)
    {
        $this->voiceToTextService = $voiceToTextService;
    }

    public function transcribe(Request $request)
    {
        $request->validate([
            'voice' => 'required|file|mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm',
        ]);

        $text = $this->voiceToTextService->transcribe(
            $request->file('voice'),
            'whisper-1',
            null,
            'text',
            0.0,
            $request->user() ? $request->user()->id : null
        );

        return response()->json(['text' => $text]);
    }
}
