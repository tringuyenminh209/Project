<?php

namespace App\Exceptions;

// E004 二重打刻 → 409 Conflict (12_詳細設計書 mục 11)
use Illuminate\Http\JsonResponse;

class DuplicateAttendanceException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'E004',
                'message' => '本日は既に打刻済みです。',
            ],
        ], 409);
    }
}
