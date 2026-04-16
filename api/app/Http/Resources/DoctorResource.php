<?php

namespace App\Http\Resources;

use App\Services\SettingsService;
use App\Support\PublicStorageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hp = $this->healthcareProfessional;
        $quals = is_array($hp?->qualifications) ? $hp->qualifications : [];
        $qualSummary = collect($quals)->filter(fn ($q) => is_string($q) && trim($q) !== '')->take(3)->implode(' · ');
        $settings = app(SettingsService::class);
        $doctorSetCharge = $hp && $hp->consultation_charge !== null;
        $effectiveFee = $settings->getConsultationAmountForDoctor($hp);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'speciality' => $hp?->speciality,
            'professional_number' => $hp?->professional_number,
            'institution' => $hp?->institution?->name,
            'profile_photo_url' => $this->profile_photo_path
                ? PublicStorageUrl::url($request, $this->profile_photo_path)
                : null,
            /** Explicit rate set by the clinician, if any (null = use category default). */
            'consultation_fee' => $doctorSetCharge
                ? (float) $hp->consultation_charge
                : null,
            /** Amount the patient should expect to pay (clinician rate or category default). */
            'effective_consultation_fee' => $effectiveFee,
            'consultation_fee_is_custom' => (bool) $doctorSetCharge,
            'bio' => $hp?->bio,
            'qualifications_summary' => $qualSummary !== '' ? $qualSummary : null,
            'license_number' => $hp?->license_number,
            'regulatory_council' => $hp?->regulatory_council,
        ];
    }
}
