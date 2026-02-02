<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class WaterComparisonRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'device_id.integer' => 'The device ID must be a valid integer.',
            'device_id.exists' => 'The specified device does not exist.',
            'days.integer' => 'The days parameter must be an integer.',
            'days.min' => 'The days parameter must be at least 1.',
            'days.max' => 'The days parameter cannot exceed 90.',
        ];
    }
}

