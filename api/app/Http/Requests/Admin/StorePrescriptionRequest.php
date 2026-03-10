<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
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
        return [
            'consultation_id' => ['required', 'integer', 'exists:consultations,id'],
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'doctor')),
            ],
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'patient')),
            ],
            'medications' => ['required', 'array', 'min:1'],
            'medications.*.name' => ['required', 'string', 'max:255'],
            'medications.*.form' => ['nullable', 'string', Rule::in(['Tablet', 'Capsule', 'Suppository', 'Syrup'])],
            'medications.*.dosage' => ['nullable', 'string', 'max:255'],
            'medications.*.frequency' => ['nullable', 'string', 'max:255'],
            'medications.*.duration' => ['nullable', 'string', 'max:255'],
            'medications.*.instructions' => ['nullable', 'string', 'max:65535'],
            'instructions' => ['nullable', 'string'],
            'issued_at' => ['required', 'date'],
            'status' => ['sometimes', Rule::in(['active', 'completed', 'cancelled'])],
        ];
    }
}
