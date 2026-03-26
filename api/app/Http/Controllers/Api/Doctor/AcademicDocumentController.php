<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\AcademicDocument;
use App\Models\HealthcareProfessional;
use App\Support\PublicStorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcademicDocumentController extends Controller
{
    /**
     * @return array<string, string>
     */
    private function allowedTypes(): array
    {
        return [
            'o_level' => 'O level certificate',
            'a_level' => 'A level certificate',
            'bachelors' => "Bachelor's degree certificate",
            'masters_or_fellowship' => 'Masters or fellowship certificate',
            'medical_council_registration' => 'Registration certificate with medical council',
            'annual_practicing_license' => 'Annual practicing license',
            'cv' => 'Curriculum Vitae',
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var HealthcareProfessional|null $profile */
        $profile = HealthcareProfessional::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return response()->json([
                'data' => [],
                'message' => 'Doctor profile not found. Please contact an administrator.',
            ]);
        }

        $documents = $profile->academicDocuments()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (AcademicDocument $doc) use ($request) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'name' => $doc->original_name,
                    'url' => PublicStorageUrl::url($request, $doc->stored_path),
                    'mime_type' => $doc->mime_type,
                    'size' => $doc->size,
                    'uploaded_at' => $doc->created_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $documents]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var HealthcareProfessional|null $profile */
        $profile = HealthcareProfessional::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return response()->json([
                'message' => 'Doctor profile not found. Please contact an administrator.',
            ], 422);
        }

        $allowedTypes = array_keys($this->allowedTypes());

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', $allowedTypes)],
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:pdf,jpeg,jpg,png,webp,doc,docx',
            ],
        ]);

        $file = $validated['file'];

        $path = $file->store(
            'academic-documents/'.$profile->id,
            'public'
        );

        $document = $profile->academicDocuments()->create([
            'type' => $validated['type'],
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'data' => [
                'id' => $document->id,
                'type' => $document->type,
                'name' => $document->original_name,
                'url' => PublicStorageUrl::url($request, $document->stored_path),
                'mime_type' => $document->mime_type,
                'size' => $document->size,
                'uploaded_at' => $document->created_at?->toISOString(),
            ],
        ], 201);
    }

    public function destroy(Request $request, AcademicDocument $academicDocument): JsonResponse
    {
        $user = $request->user();

        $ownsDocument = HealthcareProfessional::query()
            ->where('user_id', $user->id)
            ->whereHas('academicDocuments', function ($query) use ($academicDocument): void {
                $query->where('id', $academicDocument->id);
            })
            ->exists();

        if (! $ownsDocument) {
            abort(403, 'You are not allowed to delete this document.');
        }

        if ($academicDocument->stored_path) {
            Storage::disk('public')->delete($academicDocument->stored_path);
        }

        $academicDocument->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}

