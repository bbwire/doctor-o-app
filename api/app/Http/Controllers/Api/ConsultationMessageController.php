<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Support\PublicStorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConsultationMessageController extends Controller
{
    /**
     * List messages for a consultation. User must be the doctor or patient.
     */
    public function index(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        $messages = $consultation->messages()
            ->get()
            ->map(fn (ConsultationMessage $m) => $this->formatMessage($request, $m));

        return response()->json(['data' => $messages]);
    }

    /**
     * Store a new message. User must be the doctor or patient.
     */
    public function store(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        $user = $request->user();
        $sender = $user->id === (int) $consultation->doctor_id ? 'doctor' : 'patient';

        $validated = $request->validate([
            'text' => ['nullable', 'string', 'max:65535'],
            // max is kilobytes (Laravel); 2048 KB = 2 MB
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $hasImage = $request->hasFile('image');
        $textContent = trim((string) ($validated['text'] ?? ''));

        if (! $hasImage && $textContent === '') {
            throw ValidationException::withMessages([
                'text' => 'Enter a message or attach an image.',
            ]);
        }

        $attachmentUrl = null;
        if ($hasImage) {
            $path = $request->file('image')->store(
                'consultation-messages/' . $consultation->id,
                'public'
            );
            $attachmentUrl = PublicStorageUrl::url($request, $path);
        }

        $message = $consultation->messages()->create([
            'user_id' => $user->id,
            'sender' => $sender,
            'text' => $textContent,
            'attachment_url' => $attachmentUrl,
        ]);

        return response()->json([
            'data' => $this->formatMessage($request, $message),
        ], 201);
    }

    private function formatMessage(Request $request, ConsultationMessage $m): array
    {
        $attachmentUrl = $m->attachment_url
            ? PublicStorageUrl::normalize($request, $m->attachment_url)
            : null;

        return [
            'id' => $m->id,
            'text' => $m->text,
            'sender' => $m->sender,
            'at' => $m->created_at->toISOString(),
            'attachment_url' => $attachmentUrl,
            'source' => $m->source ?? 'user',
        ];
    }

    private function authorizeParticipant(Request $request, Consultation $consultation): void
    {
        $user = $request->user();
        $isDoctor = $user->id === (int) $consultation->doctor_id;
        $isPatient = $user->id === (int) $consultation->patient_id;

        if (! $isDoctor && ! $isPatient) {
            abort(403, 'You are not a participant in this consultation.');
        }
    }
}
