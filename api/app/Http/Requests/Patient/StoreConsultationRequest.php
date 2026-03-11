<?php

namespace App\Http\Requests\Patient;

use Carbon\Carbon;
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
        $minTime = Carbon::now()->subMinutes(15);
        $specialityOptions = [
            'General Doctor',
            'Physician',
            'Surgeon',
            'Paediatrician',
            'Nurse',
            'Pharmacist',
            'Gynecologist',
            'Dentist',
        ];

        return [
            'category' => [
                'required',
                'string',
                Rule::in($specialityOptions),
            ],
            'doctor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->where('role', 'doctor')),
            ],
            'scheduled_at' => [
                'required',
                'date',
                'after:' . $minTime->toDateTimeString(),
            ],
            'consultation_type' => ['required', Rule::in(['text', 'audio', 'video'])],
            'reason' => ['required', 'string', 'max:5000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
