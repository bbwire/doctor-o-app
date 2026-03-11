<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Models\User;
use App\Services\PatientConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    public function __construct(private readonly PatientConsultationService $patientConsultationService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $doctors = User::query()
            ->where('role', 'doctor')
            ->with('healthcareProfessional.institution')
            ->orderBy('name')
            ->get();

        return DoctorResource::collection($doctors);
    }

    public function availability(Request $request, int $doctorId): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        User::query()
            ->where('id', $doctorId)
            ->where('role', 'doctor')
            ->firstOrFail();

        $from = $request->query('from');
        $limit = (int) ($request->query('limit', 5));

        return response()->json([
            'data' => [
                'doctor_id' => $doctorId,
                'available_slots' => $this->patientConsultationService->suggestAvailableSlots(
                    $doctorId,
                    is_string($from) ? $from : null,
                    $limit
                ),
            ],
        ]);
    }

    public function categoryAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['required', 'string', 'in:General Doctor,Physician,Surgeon,Paediatrician,Nurse,Pharmacist,Gynecologist,Dentist'],
            'from' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $category = $request->query('category');
        $from = $request->query('from');
        $limit = (int) ($request->query('limit', 5));

        return response()->json([
            'data' => [
                'category' => $category,
                'available_slots' => $this->patientConsultationService->suggestAvailableSlotsForCategory(
                    $category,
                    is_string($from) ? $from : null,
                    $limit
                ),
            ],
        ]);
    }
}
