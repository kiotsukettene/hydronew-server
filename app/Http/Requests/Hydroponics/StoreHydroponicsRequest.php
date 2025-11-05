<?php

namespace App\Http\Requests\Hydroponics;

use Illuminate\Foundation\Http\FormRequest;

class StoreHydroponicsRequest extends FormRequest
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
        return [
            'crop_name' => 'required|string|max:255',
            'number_of_crops' => 'required|integer|min:1',
            'bed_size' => 'required|in:small,medium,large',
            'pump_config' => 'nullable|array',
            'nutrient_solution' => 'nullable|string|max:255',
            'target_ph_min' => 'required|numeric',
            'target_ph_max' => 'required|numeric',
            'target_tds_min' => 'required|integer',
            'target_tds_max' => 'required|integer',
            'water_amount' => 'required|string|max:50',
        ];
    }
}
