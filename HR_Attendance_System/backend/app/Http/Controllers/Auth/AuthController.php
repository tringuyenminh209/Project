<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Constructor Injection: khai báo cần AuthService, Laravel TỰ tạo và đưa vào (DI container).
    // Không bao giờ viết "new AuthService()" trong controller — khó test, khó thay thế.
    public function __construct(private readonly AuthService $authService){}

    // API-001. Nhận LoginRequest = validation đã chạy xong trước khi vào đây
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login(
            $request->validated('email'),     // validated() = chỉ lấy field đã qua rule
            $request->validated('password'),  //   (không lấy mù bằng ->input() — an toàn hơn)
        );

        return response()->json([
            'success' => true,
            'message' => 'ログインしました。',
            'data' => $data,
        ]);
    }

    // API-002
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました。',
            'data' => null,
        ]);
    }

    // API-003. $request->user() = employee mà Sanctum xác định từ token trong header
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => $request->user()->load(['role', 'department', 'shift']),
        ]);
    }

    // API-019. $request->user() = CHÍNH người đang cầm token (tránh lỗ hổng đổi password người khác)
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->authService->updatePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('new_password'),
        );

        return response()->json([
            'success' => true,
            'message' => 'パスワードを変更しました。',
            'data' => null,
        ]);
    }

    // API-021. Không FormRequest (GET không có body), không gọi Service (không có nghiệp vụ để tính).
    public function session(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => [
                'authenticated' => true,
                'employee' => $request->user(),
            ],
        ]);
    }
}
