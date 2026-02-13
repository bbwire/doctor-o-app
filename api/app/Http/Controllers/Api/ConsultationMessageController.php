<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ->map(fn (ConsultationMessage $m) => [
                'id' => $m->id,
                'text' => $m->text,
                'sender' => $m->sender,
                'at' => $m->created_at->toISOString(),
            ]);

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
        ]);

        $message = $consultation->messages()->create([
            'user_id' => $user->id,
            'sender' => $sender,
            'text' => $validated['text'],
        ]);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'text' => $message->text,
                'sender' => $message->sender,
                'at' => $message->created_at->toISOString(),
            ],
        ], 201);
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
