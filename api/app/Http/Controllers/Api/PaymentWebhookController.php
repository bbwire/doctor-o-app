<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileMoneyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly MobileMoneyService $mobileMoneyService
    ) {
    }

    public function handleMtnMomo(Request $request): JsonResponse
    {
        // In a real deployment, verify MTN's signature / subscription key or IP allowlist here.
        $payload = $request->all();

        Log::channel('stack')->info('MTN MoMo webhook received', ['payload' => $payload]);

        $this->mobileMoneyService->handleMtnCallback($payload);

        return response()->json(['status' => 'ok']);
    }

    public function handleAirtel(Request $request): JsonResponse
    {
        // In a real deployment, verify Airtel's signature / auth headers or IP allowlist here.
        $payload = $request->all();

        Log::channel('stack')->info('Airtel Money webhook received', ['payload' => $payload]);

        $this->mobileMoneyService->handleAirtelCallback($payload);

        return response()->json(['status' => 'ok']);
    }
}

