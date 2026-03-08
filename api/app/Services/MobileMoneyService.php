<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTopUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileMoneyService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly MtnMomoGateway $mtnGateway,
        private readonly AirtelMoneyGateway $airtelGateway,
    ) {
    }

    public function initiateWalletTopUp(User $user, float $amount, string $phoneNumber, string $provider): WalletTopUp
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Top-up amount must be greater than zero.'],
            ]);
        }

        $provider = $this->normaliseProvider($provider);
        if (! in_array($provider, ['mtn_momo', 'airtel_money'], true)) {
            throw ValidationException::withMessages([
                'provider' => ['Unsupported provider.'],
            ]);
        }

        $currency = 'UGX';

        /** @var WalletTopUp $topUp */
        $topUp = WalletTopUp::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'provider' => $provider,
            'phone_number' => $phoneNumber,
            'status' => 'pending',
        ]);

        if ($provider === 'mtn_momo') {
            $this->mtnGateway->initiateCollection($topUp);
        } else {
            $this->airtelGateway->initiateCollection($topUp);
        }

        return $topUp->fresh();
    }

    public function handleMtnCallback(array $payload): void
    {
        $referenceId = $payload['referenceId'] ?? $payload['refId'] ?? null;
        if (! is_string($referenceId) || $referenceId === '') {
            return;
        }

        /** @var WalletTopUp|null $topUp */
        $topUp = WalletTopUp::query()
            ->where('provider', 'mtn_momo')
            ->where('provider_reference', $referenceId)
            ->first();

        if (! $topUp) {
            return;
        }

        $status = strtolower((string) ($payload['status'] ?? ''));
        if ($status === '') {
            return;
        }

        $this->finaliseTopUp($topUp, $status === 'success' || $status === 'successful');
    }

    public function handleAirtelCallback(array $payload): void
    {
        $reference = $payload['data']['reference'] ?? $payload['reference'] ?? null;
        if (! is_string($reference) || $reference === '') {
            return;
        }

        /** @var WalletTopUp|null $topUp */
        $topUp = WalletTopUp::query()
            ->where('provider', 'airtel_money')
            ->where('provider_reference', $reference)
            ->first();

        if (! $topUp) {
            return;
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['data']['status'] ?? ''));
        if ($status === '') {
            return;
        }

        $this->finaliseTopUp($topUp, $status === 'success' || $status === 'successful');
    }

    protected function finaliseTopUp(WalletTopUp $topUp, bool $successful): void
    {
        if ($topUp->status === 'successful') {
            return;
        }

        DB::transaction(function () use ($topUp, $successful): void {
            $topUp->refresh();
            if ($topUp->status === 'successful') {
                return;
            }

            if (! $successful) {
                $topUp->status = 'failed';
                $topUp->save();

                return;
            }

            $user = $topUp->user()->lockForUpdate()->firstOrFail();

            $transaction = $this->walletService->topUp($user, (float) $topUp->amount);

            $meta = $transaction->meta ?? [];
            $meta['wallet_topup_id'] = $topUp->id;
            $transaction->meta = $meta;
            $transaction->save();

            $topUp->status = 'successful';
            $topUp->save();
        });
    }

    protected function normaliseProvider(string $provider): string
    {
        $provider = strtolower($provider);
        if (in_array($provider, ['mtn', 'mtn_momo', 'mtn-momo'], true)) {
            return 'mtn_momo';
        }
        if (in_array($provider, ['airtel', 'airtel_money', 'airtel-money'], true)) {
            return 'airtel_money';
        }

        return $provider;
    }
}

