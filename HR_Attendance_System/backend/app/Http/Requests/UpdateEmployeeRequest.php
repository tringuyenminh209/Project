<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'max:100'],
            // ->ignore(): loại CHÍNH record đang sửa khỏi check unique —
            // không thì sửa hồ sơ mà không đổi email cũng bị báo "trùng với chính mình"
            'email'         => ['required', 'email', 'max:255',
                Rule::unique('employees', 'email')->ignore($this->route('employee'))],
            'role_id'       => ['required', 'exists:roles,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'shift_id'      => ['nullable', 'exists:shifts,id'],
        ];
    }
}
