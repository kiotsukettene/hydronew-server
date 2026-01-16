<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class TreatmentReportRequest extends FormRequest
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
            'device_id' => ['required', 'exists:devices,id'],
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'device_id.required' => 'The device ID is required.',
            'device_id.exists' => 'The selected device does not exist.',
            'date_from.before_or_equal' => 'The start date must be before or equal to the end date.',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'days.min' => 'The days must be at least 1.',
            'days.max' => 'The days must not exceed 90.',
        ];
    }
}

