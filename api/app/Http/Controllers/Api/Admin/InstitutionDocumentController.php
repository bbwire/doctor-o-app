<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Support\PublicStorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstitutionDocumentController extends Controller
{
    public function uploadPracticingCertificate(Request $request, Institution $institution): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpeg,jpg,png,webp'],
        ]);

        $file = $validated['file'];

        if ($institution->practicing_certificate_path) {
            Storage::disk('public')->delete($institution->practicing_certificate_path);
        }

        $path = $file->store(
            'institution-certificates/'.$institution->id,
            'public'
        );

        $institution->practicing_certificate_path = $path;
        $institution->save();

        return response()->json([
            'data' => [
                'url' => PublicStorageUrl::url($request, $path),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ],
        ], 201);
    }

    public function deletePracticingCertificate(Institution $institution): JsonResponse
    {
        if ($institution->practicing_certificate_path) {
            Storage::disk('public')->delete($institution->practicing_certificate_path);
            $institution->practicing_certificate_path = null;
            $institution->save();
        }

        return response()->json(['message' => 'Practicing certificate deleted.']);
    }
}

