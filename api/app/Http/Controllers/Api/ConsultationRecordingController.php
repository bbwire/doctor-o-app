<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TranscribeConsultationRecording;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultationRecordingController extends Controller
{
    /**
     * Upload a recording of a video/audio consultation for transcription.
     * Audio is transcribed and saved as a consultation message (source=transcript).
     */
    public function store(Request $request, Consultation $consultation): JsonResponse
    {
        $user = $request->user();
        $isDoctor = $user->id === (int) $consultation->doctor_id;
        $isPatient = $user->id === (int) $consultation->patient_id;

        if (! $isDoctor && ! $isPatient) {
            abort(403, 'You are not a participant in this consultation.');
        }

        $request->validate([
            'audio' => ['required', 'file', 'mimes:webm,mp3,mp4,mpeg,mpga,m4a,wav', 'max:25600'], // 25MB
        ]);

        $file = $request->file('audio');
        $path = $file->store(
            'consultation-recordings/' . $consultation->id,
            'local'
        );

        TranscribeConsultationRecording::dispatch($consultation, $path);

        return response()->json([
            'message' => 'Recording received. Transcription will be processed shortly.',
        ], 202);
    }
}
