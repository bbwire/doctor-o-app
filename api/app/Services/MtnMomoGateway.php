<?php

namespace App\Services;

use App\Models\WalletTopUp;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MtnMomoGateway
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
        return rtrim(config('services.mtn_momo.base_url', ''), '/');
    }

    protected function primaryKey(): string
    {
        return (string) config('services.mtn_momo.collection_primary_key');
    }

    protected function apiUser(): string
    {
        return (string) config('services.mtn_momo.api_user');
    }

    protected function apiKey(): string
    {
        return (string) config('services.mtn_momo.api_key');
    }

    protected function targetEnvironment(): string
    {
        return (string) config('services.mtn_momo.target_environment', 'sandbox');
    }

    public function initiateCollection(WalletTopUp $topUp): WalletTopUp
    {
        $referenceId = (string) Str::uuid();

        $token = $this->getAccessToken();

        $payload = [
            'amount' => (string) $topUp->amount,
            'currency' => $topUp->currency,
            'externalId' => (string) $topUp->id,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $topUp->phone_number,
            ],
            'payerMessage' => 'Wallet top-up',
            'payeeNote' => 'Wallet top-up',
        ];

        $response = $this->http()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Reference-Id' => $referenceId,
                'X-Target-Environment' => $this->targetEnvironment(),
                'Ocp-Apim-Subscription-Key' => $this->primaryKey(),
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl() . '/collection/v1_0/requesttopay', $payload);

        $this->ensureAccepted($response);

        $topUp->provider_reference = $referenceId;
        $meta = $topUp->provider_metadata ?? [];
        $meta['request'] = $payload;
        $meta['response_status'] = $response->status();
        $topUp->provider_metadata = $meta;
        $topUp->save();

        return $topUp;
    }

    /**
     * MTN will later call our webhook with the reference/transaction status.
     * This gateway does not perform status polling; it only initiates the request.
     */

    protected function getAccessToken(): string
    {
        $response = $this->http()
            ->withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->primaryKey(),
            ])
            ->withBasicAuth($this->apiUser(), $this->apiKey())
            ->post($this->baseUrl() . '/collection/token/');

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to obtain MTN MoMo access token.');
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Invalid MTN MoMo access token response.');
        }

        return $token;
    }

    protected function ensureAccepted(Response $response): void
    {
        if (! in_array($response->status(), [200, 201, 202], true)) {
            throw new \RuntimeException('MTN MoMo request was not accepted.');
        }
    }
}

