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
        $notes = $this->clinical_notes ?? [];
        $isPatientView = $request->user() && (int) $request->user()->id === (int) $this->patient_id;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'consultation_number' => $this->consultation_number,
            'referral_number' => $this->referral_number,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'consultation_type' => $this->consultation_type,
            'status' => $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'patient_investigation_uploads' => $this->metadata['patient_investigation_uploads'] ?? [],
            'clinical_notes' => $isPatientView ? null : $notes,
            'consultation_summary' => $isPatientView ? [
                'summary_of_history' => $notes['summary_of_history'] ?? null,
                'differential_diagnosis' => $notes['differential_diagnosis'] ?? null,
                'management_plan' => $notes['management_plan'] ?? null,
                'final_diagnosis' => $notes['final_diagnosis'] ?? null,
            ] : null,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient?->id,
                'name' => $this->patient?->name,
                'email' => $this->patient?->email,
                'role' => $this->patient?->role,
                'patient_number' => $this->patient?->patient_number,
                'date_of_birth' => $this->patient?->date_of_birth?->format('Y-m-d'),
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
                'prescription_number' => $p->prescription_number,
                'medications' => $p->medications,
                'instructions' => $p->instructions,
                'issued_at' => $p->issued_at?->toISOString(),
                'status' => $p->status,
            ])),
            'messages' => ConsultationMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
