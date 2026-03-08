<?php

namespace App\Services;

use App\Models\HealthcareProfessional;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class HealthcareProfessionalService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = HealthcareProfessional::query()->with(['user', 'institution']);

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['institution_id'])) {
            $query->where('institution_id', $filters['institution_id']);
        }

        if (! empty($filters['speciality'])) {
            $query->where('speciality', 'like', '%'.$filters['speciality'].'%');
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('speciality', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Create a new user (doctor) and their healthcare professional profile in one flow.
     *
     * @param  array<string, mixed>  $validated  Must include name, email, password; optional phone, date_of_birth; plus professional fields.
     */
    public function createWithNewUser(array $validated): HealthcareProfessional
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'doctor',
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
        ]);

        $professionalData = [
            'user_id' => $user->id,
            'institution_id' => $validated['institution_id'] ?? null,
            'speciality' => $validated['speciality'] ?? null,
            'license_number' => $validated['license_number'] ?? null,
            'registration_date' => $validated['registration_date'] ?? null,
            'regulatory_council' => $validated['regulatory_council'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'qualifications' => $validated['qualifications'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ];

        return HealthcareProfessional::create($professionalData)->load(['user', 'institution']);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(HealthcareProfessional $healthcareProfessional, array $validated): HealthcareProfessional
    {
        $healthcareProfessional->update($validated);

        return $healthcareProfessional->refresh()->load(['user', 'institution']);
    }

    public function delete(HealthcareProfessional $healthcareProfessional): void
    {
        $healthcareProfessional->delete();
    }
}
