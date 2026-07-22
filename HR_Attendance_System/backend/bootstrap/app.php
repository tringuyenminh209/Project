<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Đặt bí danh: trong routes viết middleware('role:manager,admin') là gọi class này
        $middleware->alias(['role' => EnsureRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Chưa đăng nhập / token hết hạn → E010 (12_詳細設計書 11章)
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'E010', 'message' => 'セッションが切れました。再度ログインしてください。'],
                ], 401);
            }
        });

        // Validation fail → E003, kèm chi tiết từng field
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => 'E003',
                        'message' => '入力内容を確認してください。',
                        'details' => $e->errors(),   // {"email": ["..."], "password": ["..."]}
                    ],
                ], 422);
            }
        });

        // Route Model Binding không tìm thấy ID → E007 (12_詳細設計書 11章)
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'E007', 'message' => '対象データが見つかりません。'],
                ], 404);
            }
        });
    })->create();
