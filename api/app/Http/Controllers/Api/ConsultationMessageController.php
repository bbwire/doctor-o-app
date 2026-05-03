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

        $consultation->refresh();

        $messages = $consultation->messages()
            ->get()
            ->map(fn (ConsultationMessage $m) => $this->formatMessage($request, $m));

        return response()->json([
            'data' => $messages,
            'meta' => $this->chatPresenceMeta($consultation),
        ]);
    }

    /**
     * Typing indicator + read cursor (WhatsApp-style receipts use last_read_message_id).
     */
    public function updatePresence(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        $validated = $request->validate([
            'typing' => ['sometimes', 'boolean'],
            'last_read_message_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $isDoctor = $user->id === (int) $consultation->doctor_id;

        $updates = [];

        if (array_key_exists('typing', $validated)) {
            $column = $isDoctor ? 'doctor_typing_at' : 'patient_typing_at';
            $updates[$column] = $validated['typing'] ? now() : null;
        }

        if (array_key_exists('last_read_message_id', $validated) && $validated['last_read_message_id'] !== null) {
            $mid = (int) $validated['last_read_message_id'];
            if (! $consultation->messages()->whereKey($mid)->exists()) {
                throw ValidationException::withMessages([
                    'last_read_message_id' => 'That message does not belong to this consultation.',
                ]);
            }
            $readCol = $isDoctor ? 'doctor_last_read_message_id' : 'patient_last_read_message_id';
            $current = (int) ($consultation->{$readCol} ?? 0);
            if ($mid > $current) {
                $updates[$readCol] = $mid;
            }
        }

        if ($updates !== []) {
            $consultation->forceFill($updates)->save();
        }

        $consultation->refresh();

        return response()->json([
            'meta' => $this->chatPresenceMeta($consultation),
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function chatPresenceMeta(Consultation $consultation): array
    {
        return [
            'patient_last_read_message_id' => $consultation->patient_last_read_message_id,
            'doctor_last_read_message_id' => $consultation->doctor_last_read_message_id,
            'patient_typing_at' => $consultation->patient_typing_at?->toISOString(),
            'doctor_typing_at' => $consultation->doctor_typing_at?->toISOString(),
        ];
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
