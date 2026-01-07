<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class WaterQualityRequest extends FormRequest
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
            'system_type' => ['required', 'in:dirty_water,clean_water,hydroponics_water'],
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'interval' => ['nullable', 'in:hourly,daily,weekly'],
            'parameter' => ['nullable', 'in:ph,tds,ec,turbidity,temperature,humidity,water_level,electric_current'],
            'days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'system_type.required' => 'The system type is required.',
            'system_type.in' => 'The system type must be one of: dirty_water, clean_water, hydroponics_water.',
            'interval.in' => 'The interval must be one of: hourly, daily, weekly.',
            'parameter.in' => 'The parameter must be a valid sensor reading type.',
            'days.min' => 'The days must be at least 1.',
            'days.max' => 'The days must not exceed 90.',
        ];
    }
}

