<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, Log};
use App\Models\AI\VoiceToTextModel;

class VoiceToTextService
{
    /**
     * Transcribe audio to text using OpenAI Whisper.
     *
     * @param UploadedFile $file The audio file to transcribe.
     * @param string $model
     * @param string|null $prompt
     * @param string $response_format
     * @param float $temperature
     * @param int|null $userId
     * @return array
     */
    public function transcribe(
        UploadedFile $file,
        string $model = 'whisper-1',
        ?string $prompt = null,
        string $response_format = 'text',
        float $temperature = 0.0,
        ?int $userId = null
    ): array {
        // Validate response format
        if (!in_array($response_format, ['text', 'json', 'srt', 'verbose_json', 'vtt'])) {
            throw new \InvalidArgumentException('Invalid response format');
        }
    
        try {
            // Use the temporary file directly
            $filePath = $file->getRealPath();
            
            $response = OpenAI::audio()->transcribe([
                'model' => $model,
                'file' => $filePath,
                'prompt' => $prompt,
                'response_format' => $response_format,
                'temperature' => $temperature,
            ]);
            
            $transcript = $response->text;
            $raw_response = json_encode($response->toArray());
    
            VoiceToTextModel::create([
                'user_id' => $userId,
                'file_name' => $file->getClientOriginalName(),
                'transcript' => $transcript,
                'raw_response' => $raw_response,
            ]);
    
            return [
                'text' => $transcript,
                'raw_response' => $raw_response,
            ];
            
        } catch (\Exception $e) {
            // Add detailed error logging
            Log::error('OpenAI Whisper API error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \RuntimeException('Voice-to-text transcription failed: ' . $e->getMessage());
        }
    }
    
}
