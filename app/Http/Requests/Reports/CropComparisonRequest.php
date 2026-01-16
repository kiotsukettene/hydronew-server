<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class CropComparisonRequest extends FormRequest
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
            'crop_names' => ['required', 'array', 'min:2'],
            'crop_names.*' => ['required', 'string', 'max:100'],
            'metric' => ['nullable', 'in:weight,duration,quality'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'crop_names.required' => 'At least two crop names are required for comparison.',
            'crop_names.array' => 'Crop names must be provided as an array.',
            'crop_names.min' => 'At least two crop names are required for comparison.',
            'metric.in' => 'The metric must be one of: weight, duration, quality.',
        ];
    }
}

