<?php

namespace App\Http\Requests\Hydroponics;

use Illuminate\Foundation\Http\FormRequest;

class UpdateYieldRequest extends FormRequest
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
            'health_status' => 'nullable|string|in:good,moderate,poor',
            'growth_stage' => 'nullable|string|in:seedling,vegetative,flowering,harvest-ready',
            'harvest_status' => 'nullable|string|in:not_harvested,harvested,partial',
            'predicted_yield' => 'nullable|numeric|min:0',
            'harvest_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}

