<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $doctorId = $request->user()->id;

        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = Consultation::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('scheduled_at');

        $consultations = $query->paginate($perPage);

        return ConsultationResource::collection($consultations);
    }

    public function show(Request $request, Consultation $consultation)
    {
        abort_unless($consultation->doctor_id === $request->user()->id, 403);

        $consultation->load(['patient', 'prescriptions']);

        return new ConsultationResource($consultation);
    }

    public function update(Request $request, Consultation $consultation): JsonResponse
    {
        abort_unless($consultation->doctor_id === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
        ]);

        $consultation->update($validated);

        return response()->json([
            'data' => (new ConsultationResource($consultation->load(['patient', 'prescriptions'])))->toArray($request),
        ]);
    }
}

