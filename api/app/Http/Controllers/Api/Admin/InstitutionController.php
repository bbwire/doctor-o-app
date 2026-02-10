<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListInstitutionsRequest;
use App\Http\Requests\Admin\StoreInstitutionRequest;
use App\Http\Requests\Admin\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use App\Services\InstitutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionController extends Controller
{
    public function __construct(private readonly InstitutionService $institutionService) {}

    public function index(ListInstitutionsRequest $request): AnonymousResourceCollection
    {
        $institutions = $this->institutionService->paginate($request->validated());

        return InstitutionResource::collection($institutions);
    }

    public function store(StoreInstitutionRequest $request): JsonResponse
    {
        return (new InstitutionResource($this->institutionService->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Institution $institution): InstitutionResource
    {
        return new InstitutionResource($institution);
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): InstitutionResource
    {
        return new InstitutionResource($this->institutionService->update($institution, $request->validated()));
    }

    public function destroy(Institution $institution): JsonResponse
    {
        $this->institutionService->delete($institution);

        return response()->json(['message' => 'Institution deleted successfully']);
    }
}
