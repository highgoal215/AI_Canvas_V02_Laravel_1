<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
     * @return string
     */
    public function transcribe(UploadedFile $file, string $model = 'whisper-1', ?string $prompt = null, string $response_format = 'text', float $temperature = 0.0): string
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

        return $response;
    }
}
