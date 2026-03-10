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
            $appId = env('JITSI_APP_ID', 'vpaas-magic-cookie-1fc542849a0c4b4b8a5b8c8b8c8b8c8b');
            $apiSecret = env('JITSI_API_SECRET', 'your-secret-key-here');
            
            // For meet.jit.si, we'll use a demo approach
            // In production, you should use JaaS or self-hosted Jitsi
            if (empty($apiSecret) || $apiSecret === 'your-secret-key-here') {
                // Fallback: return a mock token for testing with meet.jit.si
                return response()->json([
                    'token' => null,
                    'message' => 'Jitsi JWT not configured. Using fallback mode.',
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
                'expiresAt' => date('Y-m-d H:i:s', $exp)
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

        return response()->json([
            'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
            'appId' => env('JITSI_APP_ID'),
            'features' => [
                'jwtEnabled' => !empty(env('JITSI_API_SECRET')),
                'moderatorAuth' => $isDoctor,
                'recording' => false,
                'livestreaming' => false
            ]
        ]);
    }
}
