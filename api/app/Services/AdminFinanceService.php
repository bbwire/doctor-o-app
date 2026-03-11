<?php

namespace App\Services;

use App\Models\ConsultationSettlement;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class AdminFinanceService
{
    /**
     * Summary totals (all time or filtered by date range).
     */
    public function summary(?string $from = null, ?string $to = null): array
    {
        $topUpQuery = WalletTransaction::query()->where('type', 'top_up');
        $chargeQuery = WalletTransaction::query()->where('type', 'consultation_charge');
        $settlementQuery = ConsultationSettlement::query();

        if ($from) {
            $topUpQuery->where('created_at', '>=', $from);
            $chargeQuery->where('created_at', '>=', $from);
            $settlementQuery->where('created_at', '>=', $from);
        }
        if ($to) {
            $topUpQuery->where('created_at', '<=', $to);
            $chargeQuery->where('created_at', '<=', $to);
            $settlementQuery->where('created_at', '<=', $to);
        }

        $totalTopUps = (float) $topUpQuery->sum('amount');
        $totalConsultationCharges = (float) abs($chargeQuery->sum('amount'));
        $totalPlatformRevenue = (float) $settlementQuery->sum('platform_fee');
        $totalDoctorEarnings = (float) $settlementQuery->sum('doctor_earning');

        return [
            'total_patient_top_ups' => round($totalTopUps, 2),
            'total_consultation_fees' => round($totalConsultationCharges, 2),
            'total_platform_revenue' => round($totalPlatformRevenue, 2),
            'total_doctor_earnings' => round($totalDoctorEarnings, 2),
        ];
    }

    /**
     * Trend data grouped by day for charts. Returns arrays keyed by date (Y-m-d).
     *
     * @return array{top_ups: array{dates: string[], values: float[]}, consultation_fees: array{dates: string[], values: float[]}, platform_revenue: array{dates: string[], values: float[]}}
     */
    public function trends(int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay()->toDateTimeString();
        $end = now()->endOfDay()->toDateTimeString();

        $topUpsByDay = WalletTransaction::query()
            ->where('type', 'top_up')
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->all();

        $chargesByDay = WalletTransaction::query()
            ->where('type', 'consultation_charge')
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(ABS(amount)) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->all();

        $platformByDay = ConsultationSettlement::query()
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(platform_fee) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->all();

        $doctorByDay = ConsultationSettlement::query()
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(doctor_earning) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->all();

        $allDates = [];
        for ($d = now()->subDays($days); $d->lte(now()); $d->addDay()) {
            $allDates[] = $d->format('Y-m-d');
        }

        $fill = function (array $byDay) use ($allDates): array {
            $values = [];
            foreach ($allDates as $date) {
                $values[] = (float) ($byDay[$date] ?? 0);
            }
            return $values;
        };

        return [
            'top_ups' => ['dates' => $allDates, 'values' => $fill($topUpsByDay)],
            'consultation_fees' => ['dates' => $allDates, 'values' => $fill($chargesByDay)],
            'platform_revenue' => ['dates' => $allDates, 'values' => $fill($platformByDay)],
            'doctor_earnings' => ['dates' => $allDates, 'values' => $fill($doctorByDay)],
        ];
    }

    /**
     * Paginated list of patient top-ups (wallet_transactions type top_up).
     */
    public function topUpsList(int $perPage = 15, ?string $from = null, ?string $to = null)
    {
        $query = WalletTransaction::query()
            ->where('type', 'top_up')
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->paginate($perPage);
    }

    /**
     * Paginated list of consultation settlements (fees + platform revenue).
     */
    public function settlementsList(int $perPage = 15, ?string $from = null, ?string $to = null)
    {
        $query = ConsultationSettlement::query()
            ->with(['consultation:id,scheduled_at,consultation_type', 'patient:id,name,email', 'doctor:id,name,email'])
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->paginate($perPage);
    }

    /**
     * Consultation revenue details with filtering.
     */
    public function consultationRevenue(int $perPage = 15, int $page = 1, ?string $period = null, ?string $type = null, ?int $doctorId = null): array
    {
        $query = ConsultationSettlement::query()
            ->with(['consultation:id,consultation_type', 'patient:id,name,email', 'doctor:id,name']);

        // Apply period filter
        if ($period && $period !== 'all') {
            $days = match($period) {
                '7days' => 7,
                '30days' => 30,
                '90days' => 90,
                'year' => 365,
                default => 30
            };
            $startDate = now()->subDays($days)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        // Apply type filter
        if ($type) {
            $query->whereHas('consultation', fn ($q) => $q->where('consultation_type', $type));
        }

        // Apply doctor filter
        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $paginator = $query->paginate($perPage);

        // Calculate summary
        $summary = $this->calculateConsultationRevenueSummary($query);

        return [
            'data' => $paginator->getCollection(),
            'total' => $paginator->total(),
            'summary' => $summary,
        ];
    }

    /**
     * Platform revenue details with trends.
     */
    public function platformRevenue(int $perPage = 15, int $page = 1, int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $query = ConsultationSettlement::query()
            ->where('created_at', '>=', $startDate)
            ->with(['consultation:id', 'patient:id,name,email', 'doctor:id,name'])
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);

        // Calculate summary
        $summary = $this->calculatePlatformRevenueSummary($query);

        return [
            'data' => $paginator->getCollection(),
            'total' => $paginator->total(),
            'summary' => $summary,
        ];
    }

    /**
     * Doctor earnings details with filtering.
     */
    public function doctorEarnings(int $perPage = 15, int $page = 1, ?string $period = null, ?string $speciality = null, ?int $doctorId = null): array
    {
        $query = ConsultationSettlement::query()
            ->with(['consultation:id,consultation_type', 'doctor:id,name,speciality', 'patient:id,name,email']);

        // Apply period filter
        if ($period && $period !== 'all') {
            $days = match($period) {
                '7days' => 7,
                '30days' => 30,
                '90days' => 90,
                'year' => 365,
                default => 30
            };
            $startDate = now()->subDays($days)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        // Apply speciality filter
        if ($speciality) {
            $query->whereHas('doctor', fn ($q) => $q->where('speciality', $speciality));
        }

        // Apply doctor filter
        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $paginator = $query->paginate($perPage);

        // Calculate summary
        $summary = $this->calculateDoctorEarningsSummary($query);

        return [
            'data' => $paginator,
            'total' => $paginator->total(),
            'summary' => $summary,
        ];
    }

    /**
     * Process doctor payouts.
     */
    public function processPayouts(): void
    {
        // Since payout_status column doesn't exist, this method can be used to
        // mark payouts as processed in a separate tracking system or log
        // For now, we'll just return success as the settlements are already recorded
    }

    /**
     * Helper: Calculate consultation revenue summary.
     */
    private function calculateConsultationRevenueSummary($query): array
    {
        $totalRevenue = $query->sum('amount_paid');
        $thisMonthRevenue = $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount_paid');
        $consultationCount = $query->count();
        $averageFee = $consultationCount > 0 ? $totalRevenue / $consultationCount : 0;

        return [
            'total_revenue' => round($totalRevenue, 2),
            'this_month' => round($thisMonthRevenue, 2),
            'total_consultations' => $consultationCount,
            'average_fee' => round($averageFee, 2),
        ];
    }

    /**
     * Helper: Calculate platform revenue summary.
     */
    private function calculatePlatformRevenueSummary($query): array
    {
        $totalRevenue = $query->sum('platform_fee');
        $thisMonthRevenue = $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('platform_fee');
        $platformRate = $this->getPlatformRate($query);

        return [
            'total_revenue' => round($totalRevenue, 2),
            'this_month' => round($thisMonthRevenue, 2),
            'platform_rate' => $platformRate,
            'total_transactions' => $query->count(),
        ];
    }

    /**
     * Helper: Calculate doctor earnings summary.
     */
    private function calculateDoctorEarningsSummary($query): array
    {
        $totalEarnings = $query->sum('doctor_earning');
        $thisMonthEarnings = $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('doctor_earning');
        $pendingPayouts = $totalEarnings; // All earnings are considered pending until processed
        $activeDoctors = $query->distinct('doctor_id')->count();

        return [
            'total_earnings' => round($totalEarnings, 2),
            'this_month' => round($thisMonthEarnings, 2),
            'pending_payouts' => round($pendingPayouts, 2),
            'active_doctors' => $activeDoctors,
        ];
    }

    /**
     * Helper: Get platform rate from query.
     */
    private function getPlatformRate($query): float
    {
        $firstRecord = $query->first();
        return $firstRecord ? ($firstRecord->platform_fee_percentage ?? 10) : 10;
    }

    /**
     * Helper: Get consultation count for a doctor.
     */
    private function getDoctorConsultationCount(int $doctorId, $query): int
    {
        return $query->where('doctor_id', $doctorId)
            ->whereHas('consultation')
            ->count();
    }
}
