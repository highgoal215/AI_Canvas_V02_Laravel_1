<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
     * @return string
     */
    public function transcribe(UploadedFile $file, string $model = 'whisper-1', ?string $prompt = null, string $response_format = 'text', float $temperature = 0.0, ?int $userId = null): string
    {
        // The OpenAI API requires a file resource, so we'll temporarily store the uploaded file
        // to get a file path, which we can then open as a stream.
        $path = $file->store('temp_audio');
        $filePath = Storage::path($path);

        try {
            $response = OpenAI::audio()->transcribe([
                'model' => $model,
                'file' => fopen($filePath, 'r'),
                'prompt' => $prompt,
                'response_format' => $response_format,
                'temperature' => $temperature,
            ]);
        } finally {
            // Clean up the temporary file.
            Storage::delete($path);
        }

        // Save to database
        VoiceToTextModel::create([
            'user_id' => $userId,
            'file_name' => $file->getClientOriginalName(),
            'transcript' => is_string($response) ? $response : json_encode($response),
            'raw_response' => is_string($response) ? null : json_encode($response),
        ]);

        return $response;
    }
}
