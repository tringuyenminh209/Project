<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class LoginFailedException extends Exception
{
    // Laravel quy ước: exception có method render() thì tự dùng nó làm response luôn
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => [
                'code' => 'E001',
                // ★ Message CỐ Ý mập mờ (14_セキュリティ設計 14章): không nói "email không tồn tại"
                //   hay "sai password" — nói cụ thể là giúp hacker dò được email nào có thật.
                'message' => 'ログインIDまたはパスワードが正しくありません。',
            ]
        ], 401);   // ← tham số 2 = HTTP status. Thiếu nó là mặc định 200 — login sai mà "OK"!
    }
}
