<?php

namespace App\Http\Requests\AccountSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8) // require at least 8 characters
                    ->letters()   // must contain letters
                    ->numbers()   // must contain numbers
                    ->mixedCase() // must contain uppercase + lowercase
                    ->symbols(),  // must contain symbols
            ],
        ];
    }
}
