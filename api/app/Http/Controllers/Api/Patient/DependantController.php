<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreDependantRequest;
use App\Models\Dependant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DependantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dependants = Dependant::query()
            ->where('patient_id', $request->user()->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Dependant $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'date_of_birth' => $d->date_of_birth?->toDateString(),
                'relationship' => $d->relationship,
            ]);

        return response()->json(['data' => $dependants]);
    }

    public function store(StoreDependantRequest $request): JsonResponse
    {
        $user = $request->user();

        $dependant = Dependant::create([
            'patient_id' => $user->id,
            'name' => $request->validated()['name'],
            'date_of_birth' => $request->validated()['date_of_birth'],
            'relationship' => $request->validated()['relationship'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'id' => $dependant->id,
                'name' => $dependant->name,
                'date_of_birth' => $dependant->date_of_birth?->toDateString(),
                'relationship' => $dependant->relationship,
            ],
        ], 201);
    }

    public function destroy(Request $request, Dependant $dependant): JsonResponse
    {
        if ($dependant->patient_id !== $request->user()->id) {
            abort(403, 'You are not allowed to manage this dependant.');
        }

        $dependant->delete();

        return response()->json(['message' => 'Dependant removed.']);
    }
}

