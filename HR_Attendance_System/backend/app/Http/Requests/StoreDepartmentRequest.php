<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // 10_API設計 6.17 / 12_詳細設計書 mục 7 — departments có 2 UNIQUE (code lẫn name)
    public function rules(): array
    {
        return [
            'department_code' => ['required', 'max:50', 'unique:departments,department_code'],
            'department_name' => ['required', 'max:100', 'unique:departments,department_name'],
        ];
    }
}
