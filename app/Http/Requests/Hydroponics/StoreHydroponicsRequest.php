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
            'target_ph_max' => 'required|numeric|min:1|max:14|gte:target_ph_min',
            'target_tds_min' => 'required|integer|min:200|max:1500',
            'target_tds_max' => 'required|integer|min:200|max:1500|gte:target_tds_min',
            'water_amount' => 'required|integer|min:1|max:100',
            'harvest_date' => 'required|date',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'crop_name.required' => 'Please enter the name of the crop you want to grow.',
            'crop_name.max' => 'The crop name is too long. Please use 255 characters or less.',
            
            'number_of_crops.required' => 'Please specify how many crops you want to grow.',
            'number_of_crops.integer' => 'The number of crops must be a whole number.',
            'number_of_crops.min' => 'You must grow at least 1 crop.',
            'number_of_crops.max' => 'You can grow a maximum of 1000 crops at once.',
            
            'bed_size.required' => 'Please select a bed size for your hydroponic system.',
            'bed_size.in' => 'Please choose a valid bed size: small, medium, large, or custom.',
            
            'nutrient_solution.max' => 'The nutrient solution name is too long. Please use 255 characters or less.',
            
            'target_ph_min.required' => 'Please enter the minimum pH level for your crops.',
            'target_ph_min.numeric' => 'The minimum pH must be a number.',
            'target_ph_min.min' => 'The minimum pH must be at least 1.',
            'target_ph_min.max' => 'The minimum pH cannot exceed 14.',
            
            'target_ph_max.required' => 'Please enter the maximum pH level for your crops.',
            'target_ph_max.numeric' => 'The maximum pH must be a number.',
            'target_ph_max.min' => 'The maximum pH must be at least 1.',
            'target_ph_max.max' => 'The maximum pH cannot exceed 14.',
            'target_ph_max.gte' => 'The maximum pH must be greater than or equal to the minimum pH.',
            
            'target_tds_min.required' => 'Please enter the minimum TDS (Total Dissolved Solids) level in PPM.',
            'target_tds_min.integer' => 'The minimum TDS must be a whole number.',
            'target_tds_min.min' => 'The minimum TDS must be at least 200 PPM.',
            'target_tds_min.max' => 'The minimum TDS cannot exceed 1500 PPM.',
            
            'target_tds_max.required' => 'Please enter the maximum TDS (Total Dissolved Solids) level in PPM.',
            'target_tds_max.integer' => 'The maximum TDS must be a whole number.',
            'target_tds_max.min' => 'The maximum TDS must be at least 200 PPM.',
            'target_tds_max.max' => 'The maximum TDS cannot exceed 1500 PPM.',
            'target_tds_max.gte' => 'The maximum TDS must be greater than or equal to the minimum TDS.',
            
            'water_amount.required' => 'Please specify the amount of water in liters.',
            'water_amount.integer' => 'The water amount must be a whole number.',
            'water_amount.min' => 'You must use at least 1 liter of water.',
            'water_amount.max' => 'The maximum water amount is 100 liters.',
            
            'harvest_date.required' => 'Please select an expected harvest date.',
            'harvest_date.date' => 'Please enter a valid date for the harvest.',
        ];
    }
}
