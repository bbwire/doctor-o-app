<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\HealthcareProfessional */
class HealthcareProfessionalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'institution_id' => $this->institution_id,
            'speciality' => $this->speciality,
            'license_number' => $this->license_number,
            'registration_date' => $this->registration_date?->toDateString(),
            'regulatory_council' => $this->regulatory_council,
            'consultation_charge' => $this->consultation_charge !== null ? (float) $this->consultation_charge : null,
            'bio' => $this->bio,
            'availability_start_time' => $this->availability_start_time?->format('H:i'),
            'availability_end_time' => $this->availability_end_time?->format('H:i'),
            'qualifications' => $this->qualifications,
            'is_active' => $this->is_active,
            'is_approved' => $this->is_approved,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'role' => $this->user?->role,
            ]),
            'institution' => new InstitutionResource($this->whenLoaded('institution')),
            'academic_documents' => $this->whenLoaded('academicDocuments', function () {
                return $this->academicDocuments->map(fn ($doc) => [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'name' => $doc->original_name,
                    'url' => $doc->stored_path ? Storage::disk('public')->url($doc->stored_path) : null,
                    'mime_type' => $doc->mime_type,
                    'size' => $doc->size,
                    'uploaded_at' => $doc->created_at?->toISOString(),
                ]);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
