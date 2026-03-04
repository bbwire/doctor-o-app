<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function topUp(User $user, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Top-up amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use ($user, $amount): WalletTransaction {
            $user->refresh();
            $user->wallet_balance = ($user->wallet_balance ?? 0) + $amount;
            $user->save();

            return WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'top_up',
                'amount' => $amount,
                'balance_after' => $user->wallet_balance,
                'meta' => null,
            ]);
        });
    }

    public function chargeForConsultation(User $user, Consultation $consultation, float $amount): WalletTransaction
    {
        if ($amount <= 0) {
            return WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'consultation_charge',
                'amount' => 0,
                'balance_after' => $user->wallet_balance ?? 0,
                'meta' => [
                    'consultation_id' => $consultation->id,
                    'note' => 'No charge configured for this consultation type.',
                ],
            ]);
        }

        return DB::transaction(function () use ($user, $consultation, $amount): WalletTransaction {
            $user->lockForUpdate();
            $user->refresh();

            $currentBalance = (float) ($user->wallet_balance ?? 0);
            if ($currentBalance < $amount) {
                throw ValidationException::withMessages([
                    'wallet' => ['You do not have enough credit for this consultation.'],
                ]);
            }

            $user->wallet_balance = $currentBalance - $amount;
            $user->save();

            return WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'consultation_charge',
                'amount' => -$amount,
                'balance_after' => $user->wallet_balance,
                'meta' => [
                    'consultation_id' => $consultation->id,
                    'consultation_type' => $consultation->consultation_type,
                ],
            ]);
        });
    }
}

