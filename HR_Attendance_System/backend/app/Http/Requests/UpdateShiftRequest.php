<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // KHÔNG có shift_code — theo quyết định thiết kế "mã ca không đổi sau khi tạo"
    // (xem ghi chú cuối khối này). Nếu bạn chọn CHO đổi thì thêm lại field này với
    // Rule::unique('shifts','shift_code')->ignore($this->route('shift')) như UpdateDepartmentRequest.
    public function rules(): array
    {
        return [
            'shift_name'    => ['required', 'max:100'],
            'start_time'    => ['required', 'date_format:H:i:s'],
            'end_time'      => ['required', 'date_format:H:i:s', 'after:start_time'],
            'break_minutes' => ['required', 'integer', 'min:0'],
        ];
    }
}
