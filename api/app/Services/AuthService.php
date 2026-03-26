<?php

namespace App\Services;

use App\Models\HealthcareProfessional;
use App\Models\User;
use App\Support\IdSystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a user and issue an API token.
     *
     * @param  array<string, mixed>  $validated
     * @return array{user: User, token: string}
     */
    public function register(array $validated): array
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'patient',
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'preferred_language' => $validated['preferred_language'] ?? null,
        ]);

        if ($user->isPatient()) {
            $user->patient_number = app(PatientNumberGenerator::class)->generate($user->created_at);
            $user->save();
        }

        if (($validated['role'] ?? '') === 'doctor') {
            $hp = HealthcareProfessional::create([
                'user_id' => $user->id,
                'institution_id' => $validated['institution_id'] ?? null,
                'speciality' => $validated['speciality'] ?? null,
                'license_number' => $validated['license_number'] ?? null,
                'registration_date' => $validated['registration_date'] ?? null,
                'regulatory_council' => $validated['regulatory_council'] ?? null,
                'is_active' => true,
            ]);

            $hp->refresh();
            $prefix = IdSystem::professionalPrefix($hp->speciality);
            $hp->professional_number = app(EntityNumberGenerator::class)->generate($prefix, $hp->created_at);
            $hp->saveQuietly();
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('healthcareProfessional.institution');

        return compact('user', 'token');
    }

    /**
     * Authenticate a user and issue an API token.
     *
     * @param  array<string, mixed>  $validated
     * @return array{user: User, token: string}
     */
    public function login(array $validated): array
    {
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Load an authenticated user's API profile relations.
     */
    public function loadProfile(User $user): User
    {
        return $user->load('healthcareProfessional.institution');
    }

    /**
     * Update authenticated user's profile.
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateProfile(User $user, array $validated): User
    {
        $attributes = Arr::only($validated, ['name', 'email', 'phone', 'date_of_birth', 'preferred_language', 'chronic_conditions']);
        if (array_key_exists('chronic_conditions', $attributes) && $user->role !== 'patient') {
            unset($attributes['chronic_conditions']);
        }

        if (($validated['profile_photo_remove'] ?? false) && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $attributes['profile_photo_path'] = null;
        }

        $profilePhoto = $validated['profile_photo'] ?? null;
        if ($profilePhoto instanceof UploadedFile) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $attributes['profile_photo_path'] = $profilePhoto->store('profile-photos', 'public');
        }

        $user->update($attributes);

        return $this->loadProfile($user->refresh());
    }
}
