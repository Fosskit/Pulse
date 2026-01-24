<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCodeGeneratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'format' => 'required|string|max:255',
            'reset_yearly' => 'boolean',
            'reset_monthly' => 'boolean',
            'padding' => 'required|integer|min:1|max:10',
        ];
    }
}
