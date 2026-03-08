<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Institution */
class InstitutionResource extends JsonResource
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
            'type' => $this->type,
            'services' => $this->services,
            'address' => $this->address,
            'location' => $this->location,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'practicing_certificate_url' => $this->practicing_certificate_path
                ? Storage::disk('public')->url($this->practicing_certificate_path)
                : null,
        ];
    }
}
