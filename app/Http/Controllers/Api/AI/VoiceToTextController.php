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
        $validated = $request->validate([
            'voice' => 'required|file|mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm|max:25000', // 25MB max
        ]);

        try {
            $result = $this->voiceToTextService->transcribe(
                $request->file('voice'),
                'whisper-1',
                $request->input('prompt'), // Make prompt optional
                $request->input('response_format', 'text'),
                $request->input('temperature', 0.0),
                $request->user()?->id
            );
            
            return response()->json($result);
            
        } catch (\Throwable $e) {
            // Return detailed error to client for debugging
            return response()->json([
                'error' => 'Voice-to-text transcription failed',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

}
