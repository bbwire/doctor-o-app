<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Consultation */
class ConsultationResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'consultation_type' => $this->consultation_type,
            'status' => $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient?->id,
                'name' => $this->patient?->name,
                'email' => $this->patient?->email,
                'role' => $this->patient?->role,
                'chronic_conditions' => $this->patient?->chronic_conditions ?? [],
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor?->id,
                'name' => $this->doctor?->name,
                'email' => $this->doctor?->email,
                'role' => $this->doctor?->role,
            ]),
            'prescriptions' => $this->whenLoaded('prescriptions', fn () => $this->prescriptions->map(fn ($p) => [
                'id' => $p->id,
                'medications' => $p->medications,
                'instructions' => $p->instructions,
                'issued_at' => $p->issued_at?->toISOString(),
                'status' => $p->status,
            ])),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
