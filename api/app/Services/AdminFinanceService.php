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
}
