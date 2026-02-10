<?php

namespace App\Http\Requests\Patient;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
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
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'doctor')),
            ],
            'scheduled_at' => [
                'required',
                'date',
                'after:now',
            ],
            'consultation_type' => ['required', Rule::in(['text', 'audio', 'video'])],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
