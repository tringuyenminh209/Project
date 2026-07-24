<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

// E005 出勤なし退勤 → 409 (message từ 06_画面設計 mục 7)
class CheckOutWithoutCheckInException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'E005',
                'message' => '退勤打刻の前に出勤打刻が必要です。'
            ],
        ], 409);
    }
}
