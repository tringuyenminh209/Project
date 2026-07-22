<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // 10_API設計 6.18 / 12 mục 7. after:start_time SO ĐƯỢC ở FormRequest
    // vì cả 2 field nằm CÙNG request (khác BR-ATT-004 của ch06 phải so với DB!)
    public function rules(): array
    {
        return [
            'shift_code'    => ['required', 'max:50', 'unique:shifts,shift_code'],
            'shift_name'    => ['required', 'max:100'],
            'start_time'    => ['required', 'date_format:H:i:s'],
            'end_time'      => ['required', 'date_format:H:i:s', 'after:start_time'],
            'break_minutes' => ['required', 'integer', 'min:0'],
        ];
    }
}
