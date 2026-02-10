<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListConsultationsRequest extends FormRequest
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
            'patient_id' => ['nullable', 'integer', 'exists:users,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'consultation_type' => ['nullable', Rule::in(['text', 'audio', 'video'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
