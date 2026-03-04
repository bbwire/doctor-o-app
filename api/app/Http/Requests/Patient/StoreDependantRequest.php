<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StoreDependantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $under18Date = now()->subYears(18)->toDateString();

        return [
            'name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'after:'.$under18Date, 'before_or_equal:'.now()->toDateString()],
            'relationship' => ['nullable', 'string', 'max:255'],
        ];
    }
}

