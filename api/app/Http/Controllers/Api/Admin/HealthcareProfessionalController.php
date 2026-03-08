<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListHealthcareProfessionalsRequest;
use App\Http\Requests\Admin\StoreHealthcareProfessionalRequest;
use App\Http\Requests\Admin\UpdateHealthcareProfessionalRequest;
use App\Http\Resources\HealthcareProfessionalResource;
use App\Models\HealthcareProfessional;
use App\Services\HealthcareProfessionalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HealthcareProfessionalController extends Controller
{
    public function __construct(private readonly HealthcareProfessionalService $healthcareProfessionalService) {}

    public function index(ListHealthcareProfessionalsRequest $request): AnonymousResourceCollection
    {
        $healthcareProfessionals = $this->healthcareProfessionalService->paginate($request->validated());

        return HealthcareProfessionalResource::collection($healthcareProfessionals);
    }

    public function store(StoreHealthcareProfessionalRequest $request): JsonResponse
    {
        return (new HealthcareProfessionalResource($this->healthcareProfessionalService->createWithNewUser($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(HealthcareProfessional $healthcareProfessional): HealthcareProfessionalResource
    {
        return new HealthcareProfessionalResource(
            $healthcareProfessional->load(['user', 'institution', 'academicDocuments'])
        );
    }

    public function update(
        UpdateHealthcareProfessionalRequest $request,
        HealthcareProfessional $healthcareProfessional
    ): HealthcareProfessionalResource {
        return new HealthcareProfessionalResource(
            $this->healthcareProfessionalService->update($healthcareProfessional, $request->validated())
        );
    }

    public function destroy(HealthcareProfessional $healthcareProfessional): JsonResponse
    {
        $this->healthcareProfessionalService->delete($healthcareProfessional);

        return response()->json(['message' => 'Healthcare professional deleted successfully']);
    }

    /**
     * Update only approval/activation status (for admin quick actions).
     */
    public function updateStatus(
        \Illuminate\Http\Request $request,
        HealthcareProfessional $healthcareProfessional
    ): HealthcareProfessionalResource {
        $validated = $request->validate([
            'is_approved' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $healthcareProfessional->update($validated);

        return new HealthcareProfessionalResource(
            $healthcareProfessional->refresh()->load(['user', 'institution', 'academicDocuments'])
        );
    }
}
