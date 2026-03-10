<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultationId = $this->input('consultation_id');
        if (! $consultationId) {
            return false;
        }

        $consultation = \App\Models\Consultation::find($consultationId);

        return $consultation && (int) $consultation->doctor_id === (int) $this->user()?->id;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'consultation_id' => ['required', 'integer', 'exists:consultations,id'],
            'medications' => ['required', 'array', 'min:1'],
            'medications.*.name' => ['required', 'string', 'max:255'],
            'medications.*.form' => ['nullable', 'string', Rule::in(['Tablet', 'Capsule', 'Suppository', 'Syrup'])],
            'medications.*.dosage' => ['nullable', 'string', 'max:255'],
            'medications.*.frequency' => ['nullable', 'string', 'max:255'],
            'medications.*.duration' => ['nullable', 'string', 'max:255'],
            'medications.*.instructions' => ['nullable', 'string', 'max:65535'],
            'instructions' => ['nullable', 'string', 'max:65535'],
            'status' => ['sometimes', Rule::in(['active', 'completed', 'cancelled'])],
        ];
    }
}
