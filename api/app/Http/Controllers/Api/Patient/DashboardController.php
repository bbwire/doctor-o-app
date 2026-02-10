<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Services\PatientDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly PatientDashboardService $patientDashboardService) {}

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->patientDashboardService->summaryForPatient($request->user()),
        ]);
    }
}
