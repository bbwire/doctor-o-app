<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthcareProfessionalRequest extends FormRequest
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
     * Creates the user (doctor) as part of creating the healthcare professional.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'speciality' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'regulatory_council' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
            'qualifications' => ['nullable', 'array'],
            'qualifications.*' => ['string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
