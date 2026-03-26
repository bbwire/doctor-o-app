<?php

namespace App\Http\Resources;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'institution_number' => $this->institution_number,
            'address' => $this->address,
            'location' => $this->location,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'practicing_certificate_url' => $this->practicing_certificate_path
                ? PublicStorageUrl::url($request, $this->practicing_certificate_path)
                : null,
        ];
    }
}
