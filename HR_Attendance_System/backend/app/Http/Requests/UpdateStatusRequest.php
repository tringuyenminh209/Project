<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

// API-016/017/018 phần 無効化・有効化 nhận CÙNG 1 field → viết 1 lần dùng 3 nơi
// (nguyên tắc: không viết trùng logic)
class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
