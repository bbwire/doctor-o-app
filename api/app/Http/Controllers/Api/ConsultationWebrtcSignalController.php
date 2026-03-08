<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationWebrtcSignal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationWebrtcSignalController extends Controller
{
    /**
     * Get WebRTC signals from the other peer (for polling).
     * Supports ?after_id=123 (preferred) or ?since=ISO8601 for cursor.
     */
    public function index(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        // Use route path to determine role so it works when same user tests as both doctor and patient
        $isDoctorRoute = str_contains($request->path(), 'doctor/consultations');
        $sender = $isDoctorRoute ? 'doctor' : 'patient';
        $otherSender = $sender === 'doctor' ? 'patient' : 'doctor';

        $query = ConsultationWebrtcSignal::query()
            ->where('consultation_id', $consultation->id)
            ->where('sender', $otherSender);

        $afterId = $request->query('after_id');
        if ($afterId !== null && $afterId !== '') {
            $query->where('id', '>', (int) $afterId);
        } else {
            $since = $request->query('since');
            if ($since) {
                try {
                    $query->where('created_at', '>', Carbon::parse($since));
                } catch (\Throwable) {
                    // ignore invalid since
                }
            }
        }

        $signals = $query->orderBy('id')->get();

        return response()->json([
            'data' => $signals->map(fn (ConsultationWebrtcSignal $s) => [
                'id' => $s->id,
                'type' => $s->type,
                'payload' => $s->payload,
                'created_at' => $s->created_at ? Carbon::parse($s->created_at)->toISOString() : now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Store a WebRTC signal (offer, answer, or ice-candidate).
     */
    public function store(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorizeParticipant($request, $consultation);

        // Use route path so same user can test as both doctor and patient (different tabs)
        $isDoctorRoute = str_contains($request->path(), 'doctor/consultations');
        $sender = $isDoctorRoute ? 'doctor' : 'patient';

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

        $createdAtIso = $signal->created_at
            ? Carbon::parse($signal->created_at)->toISOString()
            : now()->toISOString();

        return response()->json([
            'data' => [
                'id' => $signal->id,
                'type' => $signal->type,
                'created_at' => $createdAtIso,
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
