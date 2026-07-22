<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ShiftController;
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

// Group RIÊNG, gắn thêm 'role:admin' — khác nhóm auth:sanctum trần của chương 04.
// Middleware chạy nối tiếp: auth:sanctum check trước (401 nếu chưa login),
// role:admin check sau (403 nếu không phải Admin).
// auth:sanctum chạy trước (401), role:admin chạy sau (403)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Employee — API-014〜016 (không có GET danh sách theo spec)
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::patch('/employees/{employee}/status', [EmployeeController::class, 'setStatus']);

    // Department — API-017 (4 route theo bảng 10_API設計 6.17)
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::patch('/departments/{department}/status', [DepartmentController::class, 'setStatus']);

    // Shift — API-018
    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
    Route::patch('/shifts/{shift}/status', [ShiftController::class, 'setStatus']);
});
