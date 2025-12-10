<?php

namespace App\Http\Requests\Hydroponics;

use App\Models\HydroponicSetup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

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
            'total_weight' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            
            // Grades array validation
            'grades' => 'required|array|min:1',
            'grades.*.grade' => 'required|in:selling,consumption',
            'grades.*.count' => 'required|integer|min:0',
            'grades.*.weight' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $totalCount = $this->input('total_count', 0);
            $grades = $this->input('grades', []);
            
            // Calculate sum of grades counts
            $gradesSum = collect($grades)->sum('count');
            
            if ($gradesSum !== (int) $totalCount) {
                $validator->errors()->add(
                    'grades',
                    "The sum of grades counts ({$gradesSum}) must equal the total count ({$totalCount})."
                );
            }
        });
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
            'grades.required' => 'The grades breakdown is required.',
            'grades.min' => 'At least one grade entry is required.',
            'grades.*.grade.required' => 'Each grade entry must have a grade type.',
            'grades.*.grade.in' => 'Grade type must be either: selling or consumption.',
            'grades.*.count.required' => 'Each grade entry must have a count.',
            'grades.*.count.integer' => 'Grade count must be an integer.',
            'grades.*.count.min' => 'Grade count cannot be negative.',
        ];
    }
}
