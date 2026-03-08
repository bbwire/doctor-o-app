<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminFinanceService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(
        private readonly AdminFinanceService $financeService,
        private readonly SettingsService $settingsService
    ) {}

    /**
     * Finance overview: summary, platform revenue %, and trend data for charts.
     */
    public function index(Request $request): JsonResponse
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $days = max(7, min(90, (int) $request->get('days', 30)));

        $summary = $this->financeService->summary($from ?: null, $to ?: null);
        $trends = $this->financeService->trends($days);
        $platformRevenuePercentage = $this->settingsService->getPlatformRevenuePercentage();

        return response()->json([
            'data' => [
                'summary' => $summary,
                'trends' => $trends,
                'platform_revenue_percentage' => $platformRevenuePercentage,
            ],
        ]);
    }

    /**
     * Patient credit purchases (top-ups) paginated list.
     */
    public function topUps(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->get('per_page', 15)));
        $from = $request->get('from') ?: null;
        $to = $request->get('to') ?: null;

        $paginator = $this->financeService->topUpsList($perPage, $from, $to);
        $data = $paginator->getCollection()->map(fn ($t) => [
            'id' => $t->id,
            'user_id' => $t->user_id,
            'user' => $t->user ? ['id' => $t->user->id, 'name' => $t->user->name, 'email' => $t->user->email] : null,
            'amount' => (float) $t->amount,
            'created_at' => $t->created_at->toISOString(),
        ])->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Consultation fees / settlements paginated list.
     */
    public function settlements(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->get('per_page', 15)));
        $from = $request->get('from') ?: null;
        $to = $request->get('to') ?: null;

        $paginator = $this->financeService->settlementsList($perPage, $from, $to);
        $data = $paginator->getCollection()->map(fn ($s) => [
            'id' => $s->id,
            'consultation_id' => $s->consultation_id,
            'consultation_type' => $s->consultation?->consultation_type,
            'scheduled_at' => $s->consultation?->scheduled_at?->toISOString(),
            'patient' => $s->patient ? ['id' => $s->patient->id, 'name' => $s->patient->name, 'email' => $s->patient->email] : null,
            'doctor' => $s->doctor ? ['id' => $s->doctor->id, 'name' => $s->doctor->name, 'email' => $s->doctor->email] : null,
            'amount_paid' => (float) $s->amount_paid,
            'platform_fee_percentage' => (float) $s->platform_fee_percentage,
            'platform_fee' => (float) $s->platform_fee,
            'doctor_earning' => (float) $s->doctor_earning,
            'created_at' => $s->created_at->toISOString(),
        ])->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
