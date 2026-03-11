<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JitsiController extends Controller
{
    /**
     * Generate JWT token for Jitsi authentication
     */
    public function generateToken(Request $request)
    {
        try {
            $validated = $request->validate([
                'roomName' => 'required|string',
                'isModerator' => 'boolean',
                'consultationId' => 'string'
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Check if user is a doctor for moderator privileges
            $isDoctor = $user->role === 'doctor';
            $isModerator = $validated['isModerator'] && $isDoctor;

            // Jitsi JWT configuration
            $appId = env('JITSI_APP_ID');
            $apiSecret = env('JITSI_API_SECRET');
            // Force the domain for now (shared hosting .env issue)
            $jitsiDomain = 'vpaas-magic-cookie-f5cbb02494b64e5da5607f1e625fdc34.8x8.vc';
            
            // Check if JaaS credentials are properly configured
            if (empty($appId) || empty($apiSecret) || $appId === 'vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b') {
                return response()->json([
                    'token' => null,
                    'message' => 'JaaS credentials not configured. Please set JITSI_APP_ID and JITSI_API_SECRET in .env',
                    'domain' => $jitsiDomain,
                    'isModerator' => $isModerator
                ]);
            }

            $now = time();
            $exp = $now + 3600; // Token valid for 1 hour

            $payload = [
                'iss' => $appId,          // Issuer
                'aud' => $appId,          // Audience
                'exp' => $exp,           // Expiration time
                'iat' => $now,           // Issued at
                'sub' => (string) $user->id, // Subject (user ID)
                'context' => [
                    'user' => [
                        'id' => (string) $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar ?? null,
                        'moderator' => $isModerator
                    ],
                    'features' => [
                        'livestreaming' => false,
                        'recording' => false,
                        'transcription' => false,
                        'outbound-call' => true
                    ]
                ],
                'room' => $validated['roomName']
            ];

            $token = JWT::encode($payload, $apiSecret, 'HS256');

            return response()->json([
                'token' => $token,
                'isModerator' => $isModerator,
                'expiresAt' => date('Y-m-d H:i:s', $exp),
                'domain' => $jitsiDomain,
                'appId' => $appId
            ]);

        } catch (\Exception $e) {
            Log::error('Jitsi token generation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to generate token',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Jitsi configuration
     */
    public function getConfig(Request $request)
    {
        $user = auth()->user();
        $isDoctor = $user && $user->role === 'doctor';
        // Force the domain for now (shared hosting .env issue)
        $jitsiDomain = 'vpaas-magic-cookie-f5cbb02494b64e5da5607f1e625fdc34.8x8.vc';
        $appId = env('JITSI_APP_ID');
        $apiSecret = env('JITSI_API_SECRET');

        return response()->json([
            'domain' => $jitsiDomain,
            'appId' => $appId,
            'features' => [
                'jwtEnabled' => !empty($appId) && !empty($apiSecret) && $appId !== 'vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b',
                'moderatorAuth' => $isDoctor,
                'recording' => false,
                'livestreaming' => false,
                'isJaaS' => $jitsiDomain !== 'meet.jit.si'
            ]
        ]);
    }
}
