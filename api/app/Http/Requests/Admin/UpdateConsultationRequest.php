<?php

namespace App\Http\Requests\Admin;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends FormRequest
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
            'patient_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'patient')),
            ],
            'doctor_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'doctor')),
            ],
            'scheduled_at' => ['sometimes', 'date'],
            'consultation_type' => ['sometimes', Rule::in(['text', 'audio', 'video'])],
            'status' => ['sometimes', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
