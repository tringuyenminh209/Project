<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    // Mỗi test: dựng DB mới tinh → chạy migration → xong test là vứt. Test không phụ thuộc nhau.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();   // nạp DatabaseSeeder → có sẵn EMP001〜004 y hệt TD-USER của test spec
    }

    /** UT-AUTH-001: login đúng → 200, có token, đúng thông tin employee */
    public function test_login_succeeds_with_valid_credentials(): void
    {
        // Pattern AAA: Arrange (dữ liệu) → Act (hành động) → Assert (kiểm chứng)
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()                                    // HTTP 200
        ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee.employee_id', 'EMP001')
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    /** UT-AUTH-002: sai password → 401 E001 */
    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpass99',
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'E001');
    }

    /** UT-AUTH-003: user inactive → 401 E001 (BR-007) */
    public function test_inactive_employee_cannot_login(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'inactive@example.com',   // EMP004: status=inactive
            'password' => 'password123',             // password ĐÚNG — vẫn phải bị chặn!
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'E001');
    }

    /** UT-AUTH-005: password 7 ký tự → 422 E003 (rule min:8) */
    public function test_login_validation_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => '1234567',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');
    }

    /**
     * UT-AUTH-010: API-019, 正常 — current/new password đúng
     * → 200, VÀ password_hash trong DB phải THỰC SỰ đổi (không chỉ tin response).
     */
    public function test_employee_can_update_password(): void
    {
        // actingAs() giả lập "đang đăng nhập với người này", khỏi gọi API login thật
        $employee = Employee::where('email', 'user@example.com')->first();

        $response = $this->actingAs($employee)->patchJson('/api/auth/password', [
            'current_password'          => 'password123',
            'new_password'              => 'newPassword456',
            'new_password_confirmation' => 'newPassword456',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        // ★ fresh() bắt buộc — biến $employee trong RAM vẫn giữ giá trị CŨ; fresh() đọc lại
        // từ DB. Thiếu fresh() là lỗi "test pass giả" rất hay gặp.
        $this->assertTrue(
            Hash::check('newPassword456', $employee->fresh()->password_hash)
        );
    }

    /** UT-AUTH-011: current password KHÔNG khớp → 422 E003, password CŨ còn nguyên */
    public function test_update_password_fails_with_wrong_current_password(): void
    {
        $employee = Employee::where('email', 'user@example.com')->first();

        $response = $this->actingAs($employee)->patchJson('/api/auth/password', [
            'current_password'          => 'saiPassword99',
            'new_password'              => 'newPassword456',
            'new_password_confirmation' => 'newPassword456',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');

        // Assert thêm: nếu chặn sai mà lỡ vẫn đổi password thì đó là bug nghiêm trọng hơn cả không chặn được.
        $this->assertTrue(
            Hash::check('password123', $employee->fresh()->password_hash)
        );
    }

    /** UT-AUTH-013: gọi API không kèm token — cùng nhánh code, cùng response E010 như token hết hạn */
    public function test_session_check_fails_without_token(): void
    {
        $response = $this->getJson('/api/auth/session');

        $response->assertStatus(401)->assertJsonPath('error.code', 'E010');
    }

    /** Bonus — chứng minh case 正常 của API-021 */
    public function test_session_check_succeeds_with_token(): void
    {
        $employee = Employee::where('email', 'user@example.com')->first();

        $response = $this->actingAs($employee)->getJson('/api/auth/session');

        $response->assertOk()->assertJsonPath('data.authenticated', true);
    }
}
