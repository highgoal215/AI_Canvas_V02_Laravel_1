<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\TextToSpeechService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TextToSpeechController extends Controller
{
    protected $textToSpeechService;

    public function __construct(TextToSpeechService $textToSpeechService)
    {
        $this->textToSpeechService = $textToSpeechService;
    }

    public function Speechgenerate(Request $request): JsonResponse
    {
        try {
            Log::info('TextToSpeechController: Request received', [
                'has_prompt' => $request->has('prompt'),
                'prompt_length' => strlen($request->input('prompt', '')),
                'voice_style' => $request->input('voiceStyle'),
                'user_id' => $request->user()?->id
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'prompt' => 'required|string|max:4096',
                'voiceStyle' => 'sometimes|in:alloy,echo,fable,onyx,nova,shimmer',
                'speed' => 'sometimes|numeric|min:0.25|max:4.0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $prompt = $request->input('prompt');
            $voiceStyle = $request->input('voiceStyle', 'alloy');
            $speed = $request->input('speed', 1.0);
            $userId = $request->user()?->id;

            // Generate audio
            $audioUrl = $this->textToSpeechService->Speechgenerate(
                $prompt,
                $voiceStyle,
                'tts-1',
                'mp3',
                $speed,
                $userId
            );

            Log::info('TextToSpeechController: Speech generation completed', [
                'audio_url' => $audioUrl,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Audio generated successfully',
                'data' => [
                    'audio_url' => $audioUrl,
                    'voice_style' => $voiceStyle,
                    'speed' => $speed,
                    'format' => 'mp3'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('TextToSpeechController: Speech generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Provide specific error messages
            $errorMessage = 'Failed to generate audio';
            if (str_contains($e->getMessage(), 'Invalid response from OpenAI')) {
                $errorMessage = 'Audio generation service is temporarily unavailable. Please try again later.';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'The request timed out. Please try with a shorter text.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
