<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:255|unique:patients,code',
            'surname' => 'nullable|string|max:191',
            'name' => 'nullable|string|max:191',
            'telephone' => 'nullable|string|max:191',
            'sex' => 'required|in:M,F,O,U',
            'birthdate' => 'nullable|date',
            'multiple_birth' => 'nullable|boolean',
            'nationality_id' => 'required|integer|exists:nationalities,id',
            'marital_status_id' => 'nullable|integer|exists:marital_statuses,id',
            'occupation_id' => 'nullable|integer|exists:occupations,id',
            'deceased' => 'nullable|boolean',
            'deceased_at' => 'nullable|date|required_if:deceased,true',
        ];
    }
}
