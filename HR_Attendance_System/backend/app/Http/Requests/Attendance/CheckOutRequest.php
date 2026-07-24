<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // KHÔNG có rule "sau giờ vào" ở đây — giờ vào nằm trong DB,
    // FormRequest không nhìn thấy → BR-ATT-004 thuộc về Service
    public function rules(): array
    {
        return [
            'work_date'      => ['required', 'date'],
            'check_out_time' => ['required', 'date_format:H:i:s'],
        ];
    }
}
