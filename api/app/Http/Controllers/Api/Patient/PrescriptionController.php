<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\ListPrescriptionsRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Services\PatientPrescriptionService;
use App\Services\PrescriptionPatientPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PatientPrescriptionService $patientPrescriptionService) {}

    public function index(ListPrescriptionsRequest $request): AnonymousResourceCollection
    {
        $prescriptions = $this->patientPrescriptionService->paginateForPatient($request->user(), $request->validated());

        return PrescriptionResource::collection($prescriptions);
    }

    public function download(Request $request, Prescription $prescription, PrescriptionPatientPdfService $pdfService): Response
    {
        return $pdfService->download($request, $prescription);
    }

    public function acknowledgeReceipt(Request $request, Prescription $prescription): JsonResponse
    {
        $updated = $this->patientPrescriptionService->acknowledgePharmacyReceipt($request->user(), $prescription);

        return (new PrescriptionResource($updated))->response();
    }
}
