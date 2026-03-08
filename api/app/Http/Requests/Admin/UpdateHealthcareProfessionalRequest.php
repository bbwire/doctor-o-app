<?php

namespace App\Http\Requests\Admin;

use App\Models\HealthcareProfessional;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHealthcareProfessionalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeHealthcareProfessional = $this->route('healthcare_professional');
        $healthcareProfessionalId = $routeHealthcareProfessional instanceof HealthcareProfessional
            ? $routeHealthcareProfessional->id
            : $routeHealthcareProfessional;

        return [
            'user_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'doctor')),
                Rule::unique('healthcare_professionals', 'user_id')->ignore($healthcareProfessionalId),
            ],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'speciality' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'regulatory_council' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_approved' => ['sometimes', 'boolean'],
        ];
    }
}
