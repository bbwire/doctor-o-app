<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'permissions' => $this->when($this->isAdmin(), fn () => $this->permissions ?? []),
            'is_super_admin' => $this->isSuperAdmin(),
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'wallet_balance' => $this->when($this->isPatient(), fn () => (float) ($this->wallet_balance ?? 0)),
            'preferred_language' => $this->preferred_language,
            'profile_photo_url' => $this->profile_photo_path ? Storage::disk('public')->url($this->profile_photo_path) : null,
            'healthcare_professional' => new HealthcareProfessionalResource($this->whenLoaded('healthcareProfessional')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
