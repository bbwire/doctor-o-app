<?php

namespace App\Services;

use App\Models\WalletTopUp;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

class AirtelMoneyGateway
{
    public function __construct(
        private readonly HttpFactory $httpFactory
    ) {
    }

    protected function http(): HttpFactory
    {
        return $this->httpFactory;
    }

    protected function baseUrl(): string
    {
        return rtrim(config('services.airtel_money.base_url', ''), '/');
    }

    protected function clientId(): string
    {
        return (string) config('services.airtel_money.client_id');
    }

    protected function clientSecret(): string
    {
        return (string) config('services.airtel_money.client_secret');
    }

    protected function merchantMsisdn(): string
    {
        return (string) config('services.airtel_money.merchant_msisdn');
    }

    public function initiateCollection(WalletTopUp $topUp): WalletTopUp
    {
        $token = $this->getAccessToken();

        $reference = 'WT-' . $topUp->id . '-' . time();

        $payload = [
            'reference' => $reference,
            'subscriber' => [
                'country' => 'UG',
                'currency' => $topUp->currency,
                'msisdn' => $topUp->phone_number,
            ],
            'transaction' => [
                'amount' => (string) $topUp->amount,
                'country' => 'UG',
                'currency' => $topUp->currency,
                'id' => (string) $topUp->id,
            ],
        ];

        $response = $this->http()
            ->withToken($token)
            ->post($this->baseUrl() . '/merchant/v1/payments/', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Airtel Money request was not accepted.');
        }

        $topUp->provider_reference = $reference;
        $meta = $topUp->provider_metadata ?? [];
        $meta['request'] = $payload;
        $meta['response'] = $response->json();
        $topUp->provider_metadata = $meta;
        $topUp->save();

        return $topUp;
    }

    protected function getAccessToken(): string
    {
        $response = $this->http()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->post($this->baseUrl() . '/auth/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to obtain Airtel Money access token.');
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Invalid Airtel Money access token response.');
        }

        return $token;
    }
}

