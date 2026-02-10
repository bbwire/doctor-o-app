<?php

namespace App\Services;

use App\Models\HealthcareProfessional;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): HealthcareProfessional
    {
        return HealthcareProfessional::create($validated)->load(['user', 'institution']);
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
