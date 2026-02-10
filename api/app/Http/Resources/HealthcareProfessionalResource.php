<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'bio' => $this->bio,
            'qualifications' => $this->qualifications,
            'is_active' => $this->is_active,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'role' => $this->user?->role,
            ]),
            'institution' => new InstitutionResource($this->whenLoaded('institution')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
