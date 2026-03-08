<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSettlement;
use App\Models\PayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    /**
     * Wallet summary: balance and recent earnings.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = (float) ($user->doctor_wallet_balance ?? 0);
        $recentEarnings = ConsultationSettlement::query()
            ->where('doctor_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ConsultationSettlement $s) => [
                'consultation_id' => $s->consultation_id,
                'amount' => (float) $s->doctor_earning,
                'created_at' => $s->created_at->toISOString(),
            ]);

        return response()->json([
            'data' => [
                'balance' => round($balance, 2),
                'recent_earnings' => $recentEarnings,
            ],
        ]);
    }

    /**
     * List payout requests for the authenticated doctor.
     */
    public function payoutRequests(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->get('per_page', 15)));
        $items = PayoutRequest::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = $items->through(fn (PayoutRequest $p) => [
            'id' => $p->id,
            'amount' => (float) $p->amount,
            'status' => $p->status,
            'requested_at' => $p->requested_at?->toISOString(),
            'processed_at' => $p->processed_at?->toISOString(),
            'notes' => $p->notes,
            'created_at' => $p->created_at->toISOString(),
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * Create a new payout request.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = (float) ($user->doctor_wallet_balance ?? 0);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:999999.99'],
        ]);
        $amount = (float) $validated['amount'];

        if ($amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => ['Requested amount exceeds your available balance.'],
            ]);
        }

        $pending = PayoutRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        if ($pending) {
            throw ValidationException::withMessages([
                'amount' => ['You already have a pending payout request. Wait for it to be processed.'],
            ]);
        }

        $payout = PayoutRequest::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $payout->id,
                'amount' => (float) $payout->amount,
                'status' => $payout->status,
                'requested_at' => $payout->requested_at->toISOString(),
                'created_at' => $payout->created_at->toISOString(),
            ],
        ], 201);
    }
}
