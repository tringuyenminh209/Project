<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

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

// Public - chưa đăng nhập ai cũng gọi được
Route::post('/auth/login', [AuthController::class, 'login']);

// Bọc trong auth:sanctum - không có token hợp lệ thì 401 trước khi vào controller
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/password', [AuthController::class, 'updatePassword']);
    Route::get('/auth/session', [AuthController::class, 'session']);
});
