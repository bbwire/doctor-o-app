<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    /**
     * Create a new user (patient, doctor, or admin).
     *
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated): User
    {
        $attrs = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'chronic_conditions' => ($validated['role'] ?? '') === 'patient' ? ($validated['chronic_conditions'] ?? []) : null,
        ];
        if (in_array($validated['role'] ?? '', ['admin', 'super_admin'], true)) {
            $attrs['permissions'] = $validated['role'] === 'super_admin' ? null : ($validated['permissions'] ?? []);
        }
        $user = User::create($attrs);

        return $user->load('healthcareProfessional.institution');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with('healthcareProfessional.institution');

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage);
    }

    public function update(User $user, array $validated): User
    {
        $attrs = array_diff_key($validated, array_flip(['password']));
        if (isset($validated['password']) && $validated['password']) {
            $attrs['password'] = Hash::make($validated['password']);
        }
        if (array_key_exists('permissions', $validated)) {
            $attrs['permissions'] = in_array($user->role, ['admin', 'super_admin'], true)
                ? ($user->role === 'super_admin' ? null : ($validated['permissions'] ?? []))
                : $user->permissions;
        }
        if (array_key_exists('chronic_conditions', $validated)) {
            $attrs['chronic_conditions'] = $user->role === 'patient' ? ($validated['chronic_conditions'] ?? []) : $user->chronic_conditions;
        }
        $user->update($attrs);

        return $user->load('healthcareProfessional.institution');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
