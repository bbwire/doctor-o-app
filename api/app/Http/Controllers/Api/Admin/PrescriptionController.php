<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListPrescriptionsRequest;
use App\Http\Requests\Admin\StorePrescriptionRequest;
use App\Http\Requests\Admin\UpdatePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrescriptionController extends Controller
{
    public function __construct(private readonly PrescriptionService $prescriptionService) {}

    public function index(ListPrescriptionsRequest $request): AnonymousResourceCollection
    {
        $prescriptions = $this->prescriptionService->paginate($request->validated());

        return PrescriptionResource::collection($prescriptions);
    }

    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        return (new PrescriptionResource($this->prescriptionService->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Prescription $prescription): PrescriptionResource
    {
        return new PrescriptionResource($prescription->load(['consultation', 'patient', 'doctor']));
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription): PrescriptionResource
    {
        return new PrescriptionResource($this->prescriptionService->update($prescription, $request->validated()));
    }

    public function destroy(Prescription $prescription): JsonResponse
    {
        $this->prescriptionService->delete($prescription);

        return response()->json(['message' => 'Prescription deleted successfully']);
    }
}
