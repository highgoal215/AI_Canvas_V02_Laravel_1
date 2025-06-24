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

    /**
     * Regenerate speech using the same parameters as a previous generation.
     */
    public function SpeechRegenerate(Request $request): JsonResponse
    {
        try {
            Log::info('TextToSpeechController: Regeneration request received', [
                'has_generation_id' => $request->has('generationId'),
                'generation_id' => $request->input('generationId'),
                'user_id' => $request->user()?->id
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'generationId' => 'required|integer|exists:text_to_speeches,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $generationId = $request->input('generationId');
            $userId = $request->user()?->id;

            // Regenerate the speech
            $audioUrl = $this->textToSpeechService->SpeechRegenerate(
                $generationId,
                $userId
            );

            Log::info('TextToSpeechController: Speech regeneration completed', [
                'original_generation_id' => $generationId,
                'audio_url' => $audioUrl,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Speech regenerated successfully',
                'data' => [
                    'audio_url' => $audioUrl,
                    'original_generation_id' => $generationId,
                    'regenerated_at' => now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('TextToSpeechController: Speech regeneration failed', [
                'error' => $e->getMessage(),
                'generation_id' => $request->input('generationId'),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Provide specific error messages
            $errorMessage = 'Failed to regenerate speech';
            if (str_contains($e->getMessage(), 'Original generation not found')) {
                $errorMessage = 'The original speech generation was not found.';
            } elseif (str_contains($e->getMessage(), 'Unauthorized access')) {
                $errorMessage = 'You are not authorized to regenerate this speech.';
            } elseif (str_contains($e->getMessage(), 'Invalid response from OpenAI')) {
                $errorMessage = 'Speech regeneration service is temporarily unavailable. Please try again later.';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'The request timed out. Please try again.';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
