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
            'number_of_crops' => 'required|integer|min:1|max:1000',
            'bed_size' => 'required|in:small,medium,large,custom',
            'pump_config' => 'nullable|array',
            'nutrient_solution' => 'nullable|string|max:255',
            'target_ph_min' => 'required|numeric|min:1|max:14',
            'target_ph_max' => 'required|numeric|min:1|max:14',
            'target_tds_min' => 'required|integer|min:1|max:10000',
            'target_tds_max' => 'required|integer|min:1|max:10000',
            'water_amount' => 'required|integer|min:1|max:100',
            'harvest_date' => 'required|date',
        ];
    }
}
