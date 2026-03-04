<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            ->map(fn (ConsultationMessage $m) => $this->formatMessage($m));

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
            'text' => ['required', 'string', 'max:65535'],
            'image' => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        $attachmentUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store(
                'consultation-messages/' . $consultation->id,
                'public'
            );
            $attachmentUrl = Storage::disk('public')->url($path);
        }

        $message = $consultation->messages()->create([
            'user_id' => $user->id,
            'sender' => $sender,
            'text' => $validated['text'],
            'attachment_url' => $attachmentUrl,
        ]);

        return response()->json([
            'data' => $this->formatMessage($message),
        ], 201);
    }

    private function formatMessage(ConsultationMessage $m): array
    {
        return [
            'id' => $m->id,
            'text' => $m->text,
            'sender' => $m->sender,
            'at' => $m->created_at->toISOString(),
            'attachment_url' => $m->attachment_url,
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
