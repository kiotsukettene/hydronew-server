<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFeedbackRequest extends FormRequest
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
            'category' => 'required|string|in:bug_report,feature_request,general_feedback,device_issue,other',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Please select a feedback category.',
            'category.in' => 'Invalid feedback category selected.',
            'message.required' => 'Please provide your feedback message.',
            'message.min' => 'Feedback message must be at least 10 characters.',
            'message.max' => 'Feedback message cannot exceed 2000 characters.',
        ];
    }
}
