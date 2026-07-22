<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Cả 2 unique đều cần ignore chính mình (bài học UpdateEmployeeRequest)
        return [
            'department_code' => ['required', 'max:50',
                Rule::unique('departments', 'department_code')->ignore($this->route('department'))],
            'department_name' => ['required', 'max:100',
                Rule::unique('departments', 'department_name')->ignore($this->route('department'))],
        ];
    }
}
