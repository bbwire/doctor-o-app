<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use App\Support\PublicStorageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        $url = PublicStorageUrl::url($request, $path);

        return response()->json([
            'url' => $url,
        ], 201);
    }

    /**
     * Patient uploads prior lab / radiology reports (PDF or images) for the assigned doctor to review under investigation results.
     */
    public function storeInvestigationUpload(Request $request, int $consultationId): JsonResponse
    {
        $consultation = Consultation::query()
            ->where('id', $consultationId)
            ->where('patient_id', $request->user()->id)
            ->firstOrFail();

        if (! in_array($consultation->status, ['scheduled', 'completed'], true)) {
            return response()->json([
                'message' => 'Uploads are only allowed for scheduled or completed consultations.',
            ], 422);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:15360'],
            'category' => ['required', 'string', Rule::in(['radiology', 'laboratory'])],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $uploaded = $validated['file'];
        $path = $uploaded->store(
            'consultation-investigations/'.$consultation->id,
            'public'
        );

        $pathNormalized = str_replace('\\', '/', $path);
        $url = PublicStorageUrl::url($request, $path);

        $record = [
            'id' => (string) Str::uuid(),
            'category' => $validated['category'],
            'file_url' => $url,
            'storage_path' => $pathNormalized,
            'original_filename' => $uploaded->getClientOriginalName(),
            'label' => $validated['label'] ?? null,
            'uploaded_at' => now()->toIso8601String(),
        ];

        $metadata = is_array($consultation->metadata) ? $consultation->metadata : [];
        $list = $metadata['patient_investigation_uploads'] ?? [];
        if (! is_array($list)) {
            $list = [];
        }
        $list[] = $record;
        $metadata['patient_investigation_uploads'] = $list;

        $consultation->update(['metadata' => $metadata]);
        $consultation->load(['patient', 'doctor', 'prescriptions']);

        return response()->json([
            'data' => [
                'upload' => $record,
                'consultation' => (new ConsultationResource($consultation))->toArray($request),
            ],
        ], 201);
    }

    /**
     * Remove a patient investigation upload and delete the stored file.
     */
    public function destroyInvestigationUpload(Request $request, int $consultationId, string $uploadId): JsonResponse
    {
        $consultation = Consultation::query()
            ->where('id', $consultationId)
            ->where('patient_id', $request->user()->id)
            ->firstOrFail();

        if (! in_array($consultation->status, ['scheduled', 'completed'], true)) {
            return response()->json([
                'message' => 'Uploads cannot be removed for this consultation.',
            ], 422);
        }

        $metadata = is_array($consultation->metadata) ? $consultation->metadata : [];
        $list = $metadata['patient_investigation_uploads'] ?? [];
        if (! is_array($list)) {
            $list = [];
        }

        $found = null;
        $idx = null;
        foreach ($list as $i => $item) {
            if (is_array($item) && isset($item['id']) && (string) $item['id'] === $uploadId) {
                $found = $item;
                $idx = $i;
                break;
            }
        }

        if ($found === null || $idx === null) {
            return response()->json([
                'message' => 'Upload not found.',
            ], 404);
        }

        $relativePath = $this->resolveInvestigationFileRelativePath($consultationId, $found);
        if ($relativePath !== null) {
            Storage::disk('public')->delete($relativePath);
        }

        array_splice($list, $idx, 1);
        $metadata['patient_investigation_uploads'] = array_values($list);

        $consultation->update(['metadata' => $metadata]);
        $consultation->load(['patient', 'doctor', 'prescriptions']);

        return response()->json([
            'data' => [
                'consultation' => (new ConsultationResource($consultation))->toArray($request),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveInvestigationFileRelativePath(int $consultationId, array $record): ?string
    {
        $expectedPrefix = 'consultation-investigations/'.$consultationId.'/';

        if (! empty($record['storage_path']) && is_string($record['storage_path'])) {
            $p = str_replace('\\', '/', $record['storage_path']);
            if (str_starts_with($p, $expectedPrefix)) {
                return $p;
            }
        }

        $url = $record['file_url'] ?? '';
        if (! is_string($url) || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $pos = strpos($path, '/storage/');
        if ($pos === false) {
            return null;
        }

        $relative = substr($path, $pos + strlen('/storage/'));
        $relative = str_replace('\\', '/', $relative);

        if (! str_starts_with($relative, $expectedPrefix)) {
            return null;
        }

        return $relative;
    }
}

