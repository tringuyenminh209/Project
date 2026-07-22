<?php

namespace Tests\Feature\Master;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** UT-MST-001: Admin đăng ký nhân viên mới → 201, status=active */
    public function test_admin_can_create_employee(): void
    {
        $admin = Employee::where('email', 'admin@example.com')->first(); // EMP003 (seeder chương 03)

        $response = $this->actingAs($admin)->postJson('/api/employees', [
            'employee_id'   => 'EMP010',
            'name'          => 'テスト 花子',
            'email'         => 'hanako@example.com',
            'password'      => 'password123',
            'role_id'       => 1,
            'department_id' => 2,
            'shift_id'      => 1,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('employees', ['employee_id' => 'EMP010', 'status' => 'active']);
    }

    /** UT-MST-002: email trùng với nhân viên có sẵn (EMP001) → 422 E003 */
    public function test_create_employee_fails_with_duplicate_email(): void
    {
        $admin = Employee::where('email', 'admin@example.com')->first();

        $response = $this->actingAs($admin)->postJson('/api/employees', [
            'employee_id'   => 'EMP010',
            'name'          => 'テスト 花子',
            'email'         => 'user@example.com', // trùng EMP001 trong seeder
            'password'      => 'password123',
            'role_id'       => 1,
            'department_id' => 2,
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');
    }

    /** UT-MST-003: password KHÔNG BAO GIỜ lưu thô — luôn kiểm chứng bằng Hash::check, không so chuỗi trực tiếp */
    public function test_employee_password_is_stored_as_hash(): void
    {
        $admin = Employee::where('email', 'admin@example.com')->first();

        $this->actingAs($admin)->postJson('/api/employees', [
            'employee_id'   => 'EMP010',
            'name'          => 'テスト 花子',
            'email'         => 'hanako@example.com',
            'password'      => 'password123',
            'role_id'       => 1,
            'department_id' => 2,
        ]);

        $created = Employee::where('employee_id', 'EMP010')->first();

        $this->assertNotEquals('password123', $created->password_hash); // KHÔNG được trùng chuỗi thô
        $this->assertTrue(Hash::check('password123', $created->password_hash));
    }

    /** UT-MST-004: Manager (không phải Admin) gọi API-014 → 403 E002 — kiểm chứng middleware role:admin */
    public function test_manager_cannot_create_employee(): void
    {
        $manager = Employee::where('email', 'manager@example.com')->first(); // EMP002

        $response = $this->actingAs($manager)->postJson('/api/employees', [
            'employee_id'   => 'EMP010',
            'name'          => 'テスト 花子',
            'email'         => 'hanako@example.com',
            'password'      => 'password123',
            'role_id'       => 1,
            'department_id' => 2,
        ]);

        $response->assertStatus(403)->assertJsonPath('error.code', 'E002');
    }

    /** UT-MST-013 (1 phần): đăng ký nhân viên phải ghi vào audit_logs — kiểm chứng AuditLogService thật sự chạy */
    public function test_employee_creation_is_logged_to_audit(): void
    {
        $admin = Employee::where('email', 'admin@example.com')->first();

        $this->actingAs($admin)->postJson('/api/employees', [
            'employee_id'   => 'EMP010',
            'name'          => 'テスト 花子',
            'email'         => 'hanako@example.com',
            'password'      => 'password123',
            'role_id'       => 1,
            'department_id' => 2,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'employee_id' => $admin->id, // người THỰC HIỆN thao tác, không phải người được tạo
            'action'      => 'employee_created',
            'target_type' => 'employees',
        ]);
    }
}
