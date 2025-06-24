<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\VoiceToTextService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VoiceToTextController extends Controller
{
    protected $voiceToTextService;

    public function __construct(VoiceToTextService $voiceToTextService)
    {
        $this->voiceToTextService = $voiceToTextService;
    }

    /**
     * Transcribe audio file to text.
     */
    public function transcribe(Request $request): JsonResponse
    {
        try {
            Log::info('VoiceToTextController: Transcription request received', [
                'has_voice_file' => $request->hasFile('voice'),
                'file_size' => $request->file('voice')?->getSize(),
                'user_id' => $request->user()?->id
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'voice' => 'required|file|mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm|max:25000', // 25MB max
                'prompt' => 'nullable|string|max:1000',
                'response_format' => 'nullable|string|in:text,json,srt,verbose_json,vtt',
                'temperature' => 'nullable|numeric|between:0,2',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('voice');
            $prompt = $request->input('prompt');
            $responseFormat = $request->input('response_format', 'text');
            $temperature = (float) $request->input('temperature', 0.0);
            $userId = $request->user()?->id;

            Log::info('VoiceToTextController: Starting transcription', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'response_format' => $responseFormat,
                'temperature' => $temperature,
                'user_id' => $userId
            ]);

            // Process the transcription
            $result = $this->voiceToTextService->transcribe(
                $file,
                'whisper-1',
                $prompt,
                $responseFormat,
                $temperature,
                $userId
            );

            Log::info('VoiceToTextController: Transcription completed successfully', [
                'transcript_length' => strlen($result['data']['transcript']),
                'user_id' => $userId
            ]);

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('VoiceToTextController: Transcription failed', [
                'error' => $e->getMessage(),
                'file_name' => $request->file('voice')?->getClientOriginalName(),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Provide specific error messages
            $errorMessage = 'Failed to transcribe audio';
            $statusCode = 500;

            if ($e instanceof \InvalidArgumentException) {
                $errorMessage = $e->getMessage();
                $statusCode = 422;
            } elseif (str_contains($e->getMessage(), 'Invalid response from OpenAI')) {
                $errorMessage = 'Audio transcription service is temporarily unavailable. Please try again later.';
            } elseif (str_contains($e->getMessage(), 'timeout')) {
                $errorMessage = 'The request timed out. Please try with a shorter audio file.';
            } elseif (str_contains($e->getMessage(), 'File size exceeds')) {
                $errorMessage = 'File size is too large. Maximum size is 25MB.';
                $statusCode = 422;
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ], $statusCode);
        }
    }
}
