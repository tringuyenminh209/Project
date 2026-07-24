<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_date'     => ['required', 'date'],
            'check_in_time' => ['required', 'date_format:H:i:s'],
        ];
    }
}
