<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;

class JitsiController extends Controller
{
    /**
     * Get the RSA private key for 8x8 JaaS JWT signing.
     * 8x8 JaaS requires RS256 (RSA) - not HS256. You must upload your public key
     * to the JaaS console and use the private key to sign tokens.
     */
    private function getPrivateKey(): ?string
    {
        $keyPath = env('JITSI_PRIVATE_KEY_PATH');
        if (! $keyPath) {
            return null;
        }

        // If a relative path is provided, resolve it relative to the Laravel base path.
        $resolvedPath = $keyPath;
        // Windows absolute path heuristic: "C:\..." or "\..." or "/"-prefixed
        $isAbsolute =
            preg_match('/^[A-Za-z]:[\\\\\\/]/', (string) $keyPath) === 1 ||
            str_starts_with((string) $keyPath, '\\\\') ||
            str_starts_with((string) $keyPath, '/') ||
            str_starts_with((string) $keyPath, '\\');

        if (! $isAbsolute) {
            $resolvedPath = base_path($keyPath);
        }

        // Normalize separators to improve Windows path handling.
        $resolvedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $resolvedPath);
        // If realpath fails (e.g. permissions), keep original resolvedPath.
        $real = realpath($resolvedPath);
        if ($real !== false) {
            $resolvedPath = $real;
        }

        if (file_exists($resolvedPath)) {
            $content = @file_get_contents($resolvedPath);
            if ($content === false) {
                Log::warning('Jitsi private key could not be read', [
                    'resolvedPath' => $resolvedPath,
                ]);
                return null;
            }
            return $content;
        }

        Log::warning('Jitsi private key file not found', [
            'inputPath' => $keyPath,
            'resolvedPath' => $resolvedPath,
        ]);
        return null;
    }

    /**
     * Generate JWT token for 8x8 JaaS authentication.
     * 8x8 JaaS requires: RS256 algorithm, kid in header, aud="jitsi", iss="chat", sub=AppID.
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

            $isDoctor = $user->role === 'doctor';
            $isModerator = $validated['isModerator'] && $isDoctor;

            $appId = env('JITSI_APP_ID');
            $keyId = env('JITSI_KEY_ID') ?: env('JITSI_API_SECRET');
            $privateKey = $this->getPrivateKey();
            $jitsiDomain = env('JITSI_DOMAIN', 'meet.jit.si');

            if (empty($appId) || empty($keyId) || empty($privateKey)) {
                return response()->json([
                    'token' => null,
                    'message' => 'JaaS requires JITSI_APP_ID, JITSI_KEY_ID (or JITSI_API_SECRET), and JITSI_PRIVATE_KEY_PATH pointing to a valid PEM RSA private key. See docs/jaas-setup-guide.md',
                    'domain' => $jitsiDomain,
                    'isModerator' => $isModerator
                ]);
            }

            $now = time();
            $exp = $now + 7200; // 2 hours (8x8 recommendation)
            $nbf = $now - 10;  // 10 seconds clock skew allowance

            // 8x8 JaaS JWT payload - aud and iss are fixed, sub is AppID
            $payload = [
                'aud' => 'jitsi',
                'iss' => 'chat',
                'sub' => $appId,
                'exp' => $exp,
                'nbf' => $nbf,
                'room' => $validated['roomName'],
                'context' => [
                    'user' => [
                        'id' => (string) $user->id,
                        'name' => $user->name,
                        'email' => $user->email ?? '',
                        'avatar' => $user->avatar ?? null,
                        // 8x8 JaaS expects "moderator" as a string ("true"/"false"), not a JSON boolean.
                        'moderator' => $isModerator ? 'true' : 'false',
                    ],
                    'features' => [
                        'livestreaming' => false,
                        'recording' => false,
                        'transcription' => false,
                        'outbound-call' => true,
                    ],
                ],
            ];

            $token = JWT::encode($payload, $privateKey, 'RS256', $keyId);

            $debug = [];
            if (env('APP_DEBUG') === true || env('APP_DEBUG') === 'true') {
                $debug = [
                    'jitsiDomain' => $jitsiDomain,
                    'appId' => $appId,
                    'kid' => $keyId,
                    'room' => $validated['roomName'],
                    'aud' => $payload['aud'],
                    'iss' => $payload['iss'],
                    'sub' => $payload['sub'],
                    'isModerator' => $isModerator,
                    'contextUserModerator' => $payload['context']['user']['moderator'] ?? null,
                    'contextUserModeratorType' => isset($payload['context']['user']['moderator']) ? gettype($payload['context']['user']['moderator']) : null,
                    'exp' => $exp,
                    'nbf' => $nbf,
                ];
            }

            return response()->json([
                'token' => $token,
                'isModerator' => $isModerator,
                'expiresAt' => date('Y-m-d H:i:s', $exp),
                'domain' => $jitsiDomain,
                'appId' => $appId,
                'debug' => $debug,
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
        $jitsiDomain = env('JITSI_DOMAIN', 'meet.jit.si');
        $appId = env('JITSI_APP_ID');
        $keyId = env('JITSI_KEY_ID') ?: env('JITSI_API_SECRET');
        $privateKey = $this->getPrivateKey();

        return response()->json([
            'domain' => $jitsiDomain,
            'appId' => $appId,
            'features' => [
                'jwtEnabled' => !empty($appId) && !empty($keyId) && !empty($privateKey),
                'moderatorAuth' => $isDoctor,
                'recording' => false,
                'livestreaming' => false,
                'isJaaS' => $jitsiDomain !== 'meet.jit.si'
            ]
        ]);
    }
}
