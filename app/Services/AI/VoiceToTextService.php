<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, Log};
use App\Models\AI\VoiceToTextModel;
use Illuminate\Support\Str;

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
        try {
            Log::info('VoiceToTextService: Starting transcription', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'model' => $model,
                'response_format' => $response_format,
                'temperature' => $temperature,
                'user_id' => $userId
            ]);

            // Validate response format
            if (!in_array($response_format, ['text', 'json', 'srt', 'verbose_json', 'vtt'])) {
                throw new \InvalidArgumentException('Invalid response format. Allowed formats: text, json, srt, verbose_json, vtt');
            }

            // Validate file size (25MB max)
            if ($file->getSize() > 25 * 1024 * 1024) {
                throw new \InvalidArgumentException('File size exceeds maximum limit of 25MB');
            }

            // Store the uploaded file
            $filename = 'audio-uploads/' . Str::random(40) . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->put($filename, file_get_contents($file->getPathname()));
            $fileUrl = Storage::disk('public')->url($filename);

            Log::info('VoiceToTextService: File stored successfully', [
                'stored_filename' => $filename,
                'file_url' => $fileUrl
            ]);

            // Prepare API request with timeout
            $options = [
                'model' => $model,
                'file' => fopen($file->getPathname(), 'r'),
                'response_format' => $response_format,
                'temperature' => $temperature,
                'timeout' => 120, // 2 minutes timeout for large files
            ];

            if ($prompt) {
                $options['prompt'] = $prompt;
            }

            Log::info('VoiceToTextService: Making OpenAI API request', [
                'options' => array_merge($options, ['file' => 'resource'])
            ]);

            $response = OpenAI::audio()->transcribe($options);

            if (!$response) {
                throw new \Exception('Invalid response from OpenAI API');
            }

            Log::info('VoiceToTextService: Received response from OpenAI', [
                'response_format' => $response_format,
                'has_text' => isset($response->text)
            ]);

            $transcript = $response->text ?? '';
            $rawResponseData = [
                'model' => $model,
                'response_format' => $response_format,
                'temperature' => $temperature,
                'transcript_length' => strlen($transcript),
                'generated_at' => now()->toISOString(),
                'file_size' => $file->getSize(),
                'file_name' => $file->getClientOriginalName(),
                'stored_file_url' => $fileUrl,
                'api_response' => $response->toArray()
            ];

            // Save to database
            VoiceToTextModel::create([
                'user_id' => $userId,
                'file_name' => $file->getClientOriginalName(),
                'transcript' => $transcript,
                'raw_response' => $rawResponseData,
            ]);

            Log::info('VoiceToTextService: Transcription completed successfully', [
                'transcript_length' => strlen($transcript),
                'user_id' => $userId
            ]);

            return [
                'success' => true,
                'message' => 'Audio transcribed successfully',
                'data' => [
                    'transcript' => $transcript,
                    'file_url' => $fileUrl,
                    'transcript_length' => strlen($transcript),
                    'model' => $model,
                    'response_format' => $response_format
                ]
            ];

        } catch (\Exception $e) {
            Log::error('VoiceToTextService: Transcription failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'user_id' => $userId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
}
