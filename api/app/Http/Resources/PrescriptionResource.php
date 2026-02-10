<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Prescription */
class PrescriptionResource extends JsonResource
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
            'consultation_id' => $this->consultation_id,
            'doctor_id' => $this->doctor_id,
            'patient_id' => $this->patient_id,
            'medications' => $this->medications,
            'instructions' => $this->instructions,
            'issued_at' => $this->issued_at?->toISOString(),
            'status' => $this->status,
            'consultation' => $this->whenLoaded('consultation', fn () => [
                'id' => $this->consultation?->id,
                'scheduled_at' => $this->consultation?->scheduled_at?->toISOString(),
                'consultation_type' => $this->consultation?->consultation_type,
                'status' => $this->consultation?->status,
            ]),
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient?->id,
                'name' => $this->patient?->name,
                'email' => $this->patient?->email,
                'role' => $this->patient?->role,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor?->id,
                'name' => $this->doctor?->name,
                'email' => $this->doctor?->email,
                'role' => $this->doctor?->role,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
