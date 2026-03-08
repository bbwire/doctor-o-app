<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        $adultDate = now()->subYears(18)->toDateString();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:patient,doctor'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.$adultDate],
            'preferred_language' => ['nullable', 'string', 'max:100'],
            'speciality' => ['nullable', 'string', 'max:255'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'regulatory_council' => ['nullable', 'string', 'max:255'],
        ];

        return $rules;
    }
}
