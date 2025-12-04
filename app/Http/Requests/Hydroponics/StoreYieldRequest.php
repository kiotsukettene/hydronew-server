<?php

namespace App\Http\Requests\Hydroponics;

use App\Models\HydroponicSetup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreYieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $setup = $this->route('setup');
        return $setup && $setup->user_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $setup = $this->route('setup');
        $maxCrops = $setup ? $setup->number_of_crops : 1;

        return [
            'total_count' => "required|integer|min:0|max:{$maxCrops}",
            'quality_grade' => 'required|in:selling,consumption,disposal',
            'total_weight' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $setup = $this->route('setup');
        $maxCrops = $setup ? $setup->number_of_crops : 1;

        return [
            'total_count.required' => 'The total count of harvested crops is required.',
            'total_count.max' => "The total count cannot exceed the number of crops in the setup ({$maxCrops}).",
            'quality_grade.required' => 'The quality grade is required.',
            'quality_grade.in' => 'The quality grade must be one of: selling, consumption, or disposal.',
        ];
    }
}

