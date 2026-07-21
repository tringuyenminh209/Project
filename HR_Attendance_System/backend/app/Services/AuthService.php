<?php

namespace App\Services;

use App\Exceptions\LoginFailedException;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /** 12_詳細設計書 5.1 — dịch từng bước pseudo-code thành code thật */
    public function login(string $email, string $password): array
    {
        // Bước 1: tìm theo email. with('role') = eager load sẵn quan hệ,
        //         tránh chạy thêm 1 query khi lát nữa đọc role_code (bệnh N+1)
        $employee = Employee::with('role')->where('email', $email)->first();

        // Bước 2: không tồn tại HOẶC đã vô hiệu hoá (BR-007) → cùng 1 lỗi E001.
        //         Gộp 2 case một chỗ cũng là để message không lộ thông tin.
        if ($employee === null || $employee->status !== 'active') {
            throw new LoginFailedException();
        }

        // Bước 3: so password thô với hash trong DB. Hash::check chậm CÓ CHỦ ĐÍCH (bcrypt) — chống brute force
        if (! Hash::check($password, $employee->password_hash)) {
            throw new LoginFailedException();
        }

        // Bước 4: phát token (Sanctum ghi 1 dòng vào personal_access_tokens).
        //         plainTextToken chỉ thấy được ĐÚNG 1 LẦN lúc này — DB chỉ lưu bản hash của token
        $token = $employee->createToken('auth')->plainTextToken;

        // Bước 5: trả về data — shape khớp Response mẫu trong 10_API設計 6.1
        return [
            'access_token' => $token,
            'employee'     => [
                'id'          => $employee->id,
                'employee_id' => $employee->employee_id,
                'name'        => $employee->name,
                'email'       => $employee->email,
                'role'        => $employee->role->role_code,
            ],
        ];
    }

    public function logout(Employee $employee): void
    {
        // Xoá đúng token đang dùng (các thiết bị khác nếu có vẫn đăng nhập)
        $employee->currentAccessToken()->delete();
    }

    /**
     * API-019 パスワード変更 (10_API設計 6.19). $employee do Controller truyền vào từ
     * $request->user() — luôn là CHÍNH người đang đăng nhập, không bao giờ nhận id từ client.
     */
    public function updatePassword(Employee $employee, string $currentPassword, string $newPassword): void
    {
        // Bước 1: bắt xác thực lại password HIỆN TẠI — có token hợp lệ không có nghĩa
        //         đổi được password tuỳ ý (phòng trường hợp token bị lộ / máy chung).
        if (! Hash::check($currentPassword, $employee->password_hash)) {
            // Dùng ValidationException có sẵn của Laravel (không tạo Exception riêng như
            // LoginFailedException) — bootstrap/app.php đã map sẵn nó thành E003.
            throw ValidationException::withMessages([
                'current_password' => ['現在のパスワードが正しくありません。'],
            ]);
        }

        // Bước 2: lưu password MỚI dưới dạng hash — TUYỆT ĐỐI không lưu chuỗi thô.
        // forceFill() thay vì update(): password_hash không nằm trong $fillable (chống
        // Mass Assignment / Privilege Escalation, 14_セキュリティ設計 8.1) nên update()
        // sẽ ÂM THẦM bỏ qua field này. Bypass CÓ CHỦ ĐÍCH ở đây vì current_password
        // đã được xác thực đúng ở bước 1 — đây là chính chủ tự đổi mật khẩu của mình.
        $employee->forceFill([
            'password_hash' => Hash::make($newPassword),
        ])->save();
    }
}
