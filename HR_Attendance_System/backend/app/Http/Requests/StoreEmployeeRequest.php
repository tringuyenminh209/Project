<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Rule chép nguyên văn 10_API設計 6.14.
    // 'exists:roles,id' = bắt lỗi role_id ma NGAY tầng validate (422 E003 sạch)
    // thay vì để FK của DB từ chối (500 xấu xí).
    public function rules(): array
    {
        return [
            'employee_id'   => ['required', 'max:50', 'unique:employees,employee_id'],
            'name'          => ['required', 'max:100'],
            'email'         => ['required', 'email', 'max:255', 'unique:employees,email'],
            'password'      => ['required', 'min:8', 'max:20'],
            'role_id'       => ['required', 'exists:roles,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'shift_id'      => ['nullable', 'exists:shifts,id'],
        ];
    }
}
