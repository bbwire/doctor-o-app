<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscriptionService
{
    /**
     * Transcribe an audio file using OpenAI Whisper API.
     * Returns the transcribed text or null if transcription is not configured or fails.
     */
    public function transcribe(string $audioPath): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (empty($apiKey)) {
            Log::warning('Transcription skipped: OPENAI_API_KEY not set.');

            return null;
        }

        if (! file_exists($audioPath) || ! is_readable($audioPath)) {
            Log::warning('Transcription skipped: audio file not found or not readable.', ['path' => $audioPath]);

            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->attach(
                    'file',
                    file_get_contents($audioPath),
                    basename($audioPath)
                )
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'response_format' => 'text',
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI Whisper API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = trim($response->body());

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Transcription failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
