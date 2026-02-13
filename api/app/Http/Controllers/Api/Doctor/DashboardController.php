<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Services\DoctorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DoctorDashboardService $doctorDashboardService) {}

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->doctorDashboardService->summaryForDoctor($request->user()),
        ]);
    }
}
