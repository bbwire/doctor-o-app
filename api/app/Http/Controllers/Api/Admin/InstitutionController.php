<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListInstitutionsRequest;
use App\Http\Requests\Admin\StoreInstitutionRequest;
use App\Http\Requests\Admin\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use App\Services\AuditLogService;
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
        $institution = $this->institutionService->create($request->validated());
        app(AuditLogService::class)->log(
            $request->user(),
            'institution.created',
            'Admin created institution: ' . ($institution->name ?? '#' . $institution->id),
            Institution::class,
            $institution->id,
            ['name' => $institution->name ?? null]
        );
        return (new InstitutionResource($institution))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Institution $institution): InstitutionResource
    {
        return new InstitutionResource($institution);
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): InstitutionResource
    {
        $updated = $this->institutionService->update($institution, $request->validated());
        app(AuditLogService::class)->log(
            $request->user(),
            'institution.updated',
            'Admin updated institution: ' . ($updated->name ?? '#' . $updated->id),
            Institution::class,
            $updated->id,
            ['name' => $updated->name ?? null]
        );
        return new InstitutionResource($updated);
    }

    public function destroy(Institution $institution): JsonResponse
    {
        $name = $institution->name ?? '#' . $institution->id;
        $id = $institution->id;
        $this->institutionService->delete($institution);
        app(AuditLogService::class)->log(
            request()->user(),
            'institution.deleted',
            'Admin deleted institution: ' . $name,
            Institution::class,
            $id,
            []
        );
        return response()->json(['message' => 'Institution deleted successfully']);
    }
}
