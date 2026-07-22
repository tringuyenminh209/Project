<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


// 12_詳細設計書 8.1 — RBAC tầng route.
// Cách dùng: Route::middleware(['auth:sanctum', 'role:manager,admin'])
class EnsureRole
{
    // ...$roles nhận phần sau dấu ":" — 'role:manager,admin' → ['manager','admin']
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();  // auth:sanctum chạy trước nên chắc chắn có user

        if ($user === null || ! in_array($user->role->role_code, $roles, true)) {
            // E002 — trả thẳng, không lộ lý do nội bộ (chỉ nói "không có quyền")
            return response()->json([
                'success' => false,
                'error' => ['code' => 'E002', 'message' => 'この操作を実行する権限がありません。'],
            ], 403);
        }

        return $next($request);  // đủ quyền → cho đi tiếp vào controller
    }
}
