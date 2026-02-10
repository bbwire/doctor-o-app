<?php

namespace App\Services;

use App\Models\User;
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
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

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
        $attributes = Arr::only($validated, ['name', 'email', 'phone', 'date_of_birth', 'preferred_language']);

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
