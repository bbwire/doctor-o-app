<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationWebrtcSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationWebrtcSignalController extends Controller
{
    /**
     * Get WebRTC signals from the other peer (for polling).
     */
    public function index(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        $user = $request->user();
        $sender = $user->id === (int) $consultation->doctor_id ? 'doctor' : 'patient';
        $otherSender = $sender === 'doctor' ? 'patient' : 'doctor';

        $since = $request->query('since');
        $query = ConsultationWebrtcSignal::query()
            ->where('consultation_id', $consultation->id)
            ->where('sender', $otherSender);

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $signals = $query->orderBy('created_at')->get();

        return response()->json([
            'data' => $signals->map(fn (ConsultationWebrtcSignal $s) => [
                'id' => $s->id,
                'type' => $s->type,
                'payload' => $s->payload,
                'created_at' => $s->created_at?->toISOString() ?? now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Store a WebRTC signal (offer, answer, or ice-candidate).
     */
    public function store(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        $user = $request->user();
        $sender = $user->id === (int) $consultation->doctor_id ? 'doctor' : 'patient';

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:offer,answer,ice-candidate'],
            'payload' => ['required'],
        ]);

        $signal = ConsultationWebrtcSignal::create([
            'consultation_id' => $consultation->id,
            'sender' => $sender,
            'type' => $validated['type'],
            'payload' => $validated['payload'],
        ]);

        return response()->json([
            'data' => [
                'id' => $signal->id,
                'type' => $signal->type,
                'created_at' => $signal->created_at?->toISOString() ?? now()->toISOString(),
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
