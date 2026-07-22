<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * 'confirmed' = Laravel tự động so field này với field "new_password_confirmation"
     * — field đó KHÔNG cần khai rule riêng, chỉ cần đúng convention tên: <field>_confirmation.
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required'],
            'new_password'      => ['required', 'min:8', 'max:20', 'confirmed'],
        ];
    }
}
