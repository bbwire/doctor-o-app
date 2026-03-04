<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (WalletTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'balance_after' => (float) $tx->balance_after,
                'meta' => $tx->meta,
                'created_at' => $tx->created_at?->toISOString(),
            ]);

        return response()->json([
            'data' => [
                'balance' => (float) ($user->wallet_balance ?? 0),
                'transactions' => $transactions,
            ],
        ]);
    }

    public function topUp(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $transaction = $this->walletService->topUp($user, (float) $validated['amount']);

        return response()->json([
            'data' => [
                'balance' => (float) ($user->fresh()->wallet_balance ?? 0),
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'balance_after' => (float) $transaction->balance_after,
                    'meta' => $transaction->meta,
                    'created_at' => $transaction->created_at?->toISOString(),
                ],
            ],
        ], 201);
    }
}

