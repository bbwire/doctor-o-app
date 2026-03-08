<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app' => ['nullable', 'array'],
            'app.name' => ['nullable', 'string', 'max:255'],
            'app.timezone' => ['nullable', 'string', 'max:64'],
            'consultations' => ['nullable', 'array'],
            'consultations.slot_interval_minutes' => ['nullable', 'integer', 'min:15', 'max:120'],
            'consultations.availability_window_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'consultations.minimum_action_lead_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
            'consultations.pricing' => ['nullable', 'array'],
            'consultations.pricing.text' => ['nullable', 'numeric', 'min:0'],
            'consultations.pricing.audio' => ['nullable', 'numeric', 'min:0'],
            'consultations.pricing.video' => ['nullable', 'numeric', 'min:0'],
            'consultations.pricing_by_speciality' => ['nullable', 'array'],
            'consultations.pricing_by_speciality.*' => ['nullable', 'numeric', 'min:0'],
            'finance' => ['nullable', 'array'],
            'finance.platform_revenue_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
