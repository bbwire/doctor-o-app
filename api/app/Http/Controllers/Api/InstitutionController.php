<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    /**
     * List active institutions (optionally filtered by type).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Institution::query()->where('is_active', true);

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $institutions = $query
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => InstitutionResource::collection($institutions),
        ]);
    }
}

