<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Services\AuditLogService;
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
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        $consultation->load(['patient', 'prescriptions']);

        return new ConsultationResource($consultation);
    }

    public function update(Request $request, Consultation $consultation): JsonResponse
    {
        abort_unless(
            (int) $consultation->doctor_id === (int) $request->user()->id,
            403,
            'You do not have access to this consultation.'
        );

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
        ]);

        $oldStatus = $consultation->status;
        $consultation->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $action = $validated['status'] === 'completed' ? 'consultation.completed' : ($validated['status'] === 'cancelled' ? 'consultation.cancelled_by_doctor' : 'consultation.status_updated');
            app(AuditLogService::class)->log(
                $request->user(),
                $action,
                'Doctor marked consultation #' . $consultation->id . ' as ' . $validated['status'],
                Consultation::class,
                $consultation->id,
                ['old_status' => $oldStatus, 'new_status' => $validated['status']]
            );
        }

        return response()->json([
            'data' => (new ConsultationResource($consultation->load(['patient', 'prescriptions'])))->toArray($request),
        ]);
    }
}

