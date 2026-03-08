<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StorePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Consultation;
use App\Models\Prescription;
use App\Services\AuditLogService;
use App\Services\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PrescriptionService $prescriptionService) {}

    public function index(Request $request)
    {
        $doctorId = $request->user()->id;

        $perPage = min((int) $request->get('per_page', 15), 100);

        $query = Prescription::query()
            ->where('doctor_id', $doctorId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('issued_at');

        $prescriptions = $query->paginate($perPage);

        return PrescriptionResource::collection($prescriptions);
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $consultation = Consultation::findOrFail($request->validated('consultation_id'));

        $payload = array_merge($request->validated(), [
            'doctor_id' => $request->user()->id,
            'patient_id' => $consultation->patient_id,
            'issued_at' => now(),
            'status' => $request->validated('status') ?? 'active',
        ]);

        $prescription = $this->prescriptionService->create($payload);
        app(AuditLogService::class)->log(
            $request->user(),
            'prescription.issued',
            'Doctor issued prescription #' . $prescription->id . ' for consultation #' . $prescription->consultation_id,
            Prescription::class,
            $prescription->id,
            ['consultation_id' => $prescription->consultation_id, 'patient_id' => $prescription->patient_id]
        );
        return (new PrescriptionResource($prescription))
            ->response()
            ->setStatusCode(201);
    }
}

