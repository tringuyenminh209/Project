<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        // Query nhẹ nhất có thể — chỉ để xác nhận kết nối DB còn sống
        DB::select('SELECT 1');
        $db = 'ok';
    } catch (\Throwable $e) {
        // health-check nuốt exception để luôn trả 200 (đúng convention health-check),
        // nhưng PHẢI tự ghi log — nuốt mà không log thì lỗi thật biến mất, không ai điều tra được
        Log::error('Health check: DB connection failed', ['error' => $e->getMessage()]);
        $db = 'error';
    }

    // Trả đúng envelope chung của 10_API設計 3.3 — tập thói quen từ API đầu tiên
    return response()->json([
        'success' => true,
        'message' => 'OK',
        'data'    => [
            'app' => 'ok',
            'db'  => $db,
            'time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
        ],
    ]);
});
