<?php

namespace App\Http\Resources;

use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ConsultationMessage */
class ConsultationMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'sender' => $this->sender,
            'at' => $this->created_at->toISOString(),
            'attachment_url' => $this->attachment_url
                ? PublicStorageUrl::normalize($request, $this->attachment_url)
                : null,
            'source' => $this->source ?? 'user',
        ];
    }
}
