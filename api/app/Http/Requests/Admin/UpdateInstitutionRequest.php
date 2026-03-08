<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstitutionRequest extends FormRequest
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
        $types = ['hospital', 'clinic', 'lab', 'drugshop', 'pharmacy', 'nursing_home'];
        $serviceOptions = ['consultation', 'pharmacy', 'lab', 'radiology', 'interventional_unit', 'nursing_care'];

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', $types)],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'in:'.implode(',', $serviceOptions)],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
