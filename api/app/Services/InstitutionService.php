<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InstitutionService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Institution::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): Institution
    {
        return Institution::create($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Institution $institution, array $validated): Institution
    {
        $institution->update($validated);

        return $institution->refresh();
    }

    public function delete(Institution $institution): void
    {
        $institution->delete();
    }
}
