<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultationMediaController extends Controller
{
    public function storeReasonImage(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $path = $validated['image']->store(
            'consultation-reasons/'.$user->id,
            'public'
        );

        $url = Storage::disk('public')->url($path);

        return response()->json([
            'url' => $url,
        ], 201);
    }
}

