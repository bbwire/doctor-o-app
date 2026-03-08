<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\HealthcareProfessionalResource;
use App\Models\HealthcareProfessional;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get the authenticated doctor's professional profile.
     */
    public function show(Request $request): HealthcareProfessionalResource|JsonResponse
    {
        $user = $request->user();

        /** @var HealthcareProfessional|null $profile */
        $profile = HealthcareProfessional::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return response()->json([
                'message' => 'Doctor profile not found. Please contact an administrator.',
            ], 404);
        }

        return new HealthcareProfessionalResource($profile);
    }

    /**
     * Update the authenticated doctor's professional profile.
     */
    public function update(Request $request): HealthcareProfessionalResource|JsonResponse
    {
        $user = $request->user();

        /** @var HealthcareProfessional|null $profile */
        $profile = HealthcareProfessional::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return response()->json([
                'message' => 'Doctor profile not found. Please contact an administrator.',
            ], 404);
        }

        $specialityOptions = [
            'General Doctor',
            'Physician',
            'Surgeon',
            'Paediatrician',
            'Nurse',
            'Pharmacist',
            'Gynecologist',
            'Dentist',
        ];

        $validated = $request->validate([
            'speciality' => ['required', 'string', 'in:'.implode(',', $specialityOptions)],
            'license_number' => ['required', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'regulatory_council' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'availability_start_time' => ['nullable', 'date_format:H:i'],
            'availability_end_time' => ['nullable', 'date_format:H:i', 'after:availability_start_time'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string', 'max:255'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'consultation_charge' => ['nullable', 'numeric', 'min:0'],
        ]);

        $profile->fill([
            'speciality' => $validated['speciality'],
            'license_number' => $validated['license_number'],
            'registration_date' => $validated['registration_date'] ?? $profile->registration_date,
            'regulatory_council' => $validated['regulatory_council'] ?? $profile->regulatory_council,
            'bio' => $validated['bio'] ?? $profile->bio,
            'availability_start_time' => $validated['availability_start_time'] ?? null,
            'availability_end_time' => $validated['availability_end_time'] ?? null,
            'qualifications' => $validated['qualifications'] ?? $profile->qualifications,
            'institution_id' => $validated['institution_id'] ?? $profile->institution_id,
            'consultation_charge' => isset($validated['consultation_charge']) && $validated['consultation_charge'] !== '' ? (float) $validated['consultation_charge'] : null,
        ]);

        $profile->save();
        app(AuditLogService::class)->log(
            $user,
            'doctor.profile_updated',
            'Doctor updated their professional profile',
            HealthcareProfessional::class,
            $profile->id,
            ['speciality' => $profile->speciality ?? null]
        );
        return new HealthcareProfessionalResource($profile->refresh()->load(['user', 'institution']));
    }
}

