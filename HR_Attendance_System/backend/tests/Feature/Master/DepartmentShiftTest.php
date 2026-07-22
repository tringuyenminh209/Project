<?php

namespace Tests\Feature\Master;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentShiftTest extends TestCase
{
    use RefreshDatabase;

    private Employee $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = Employee::where('email', 'admin@example.com')->first();
    }

    /** UT-MST-007: Admin tạo phòng ban mới → 201 */
    public function test_admin_can_create_department(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/departments', [
            'department_code' => 'dept_sales',
            'department_name' => '営業部',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('departments', ['department_code' => 'dept_sales', 'status' => 'active']);
    }

    /** UT-MST-008: tên phòng trùng (seeder có 開発部) → 422 E003 */
    public function test_duplicate_department_name_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/departments', [
            'department_code' => 'dept_dev2',
            'department_name' => '開発部', // trùng với seeder
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');
    }

    /** UT-MST-009: vô hiệu hoá phòng ban → status=inactive, KHÔNG bị xoá */
    public function test_department_can_be_deactivated(): void
    {
        $dept = Department::where('department_code', 'dept_hr')->first();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/departments/{$dept->id}/status", ['status' => 'inactive']);

        $response->assertOk();
        // Vẫn TỒN TẠI trong DB (ER-003 無効化優先) — chỉ đổi cờ
        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'status' => 'inactive']);
    }

    /** UT-MST-010: tạo shift mới → 201 */
    public function test_admin_can_create_shift(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/shifts', [
            'shift_code' => 'shift_early', 'shift_name' => '早番',
            'start_time' => '07:00:00', 'end_time' => '16:00:00', 'break_minutes' => 60,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('shifts', ['shift_code' => 'shift_early']);
    }

    /** UT-MST-011: start_time >= end_time → 422 (rule after:start_time) */
    public function test_shift_with_invalid_time_range_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/shifts', [
            'shift_code' => 'shift_bad', 'shift_name' => '不正',
            'start_time' => '18:00:00', 'end_time' => '09:00:00', 'break_minutes' => 60,
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');
    }

    /** UT-MST-012: vô hiệu hoá shift → inactive */
    public function test_shift_can_be_deactivated(): void
    {
        $shift = Shift::where('shift_code', 'shift_normal')->first();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/shifts/{$shift->id}/status", ['status' => 'inactive']);

        $response->assertOk();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'status' => 'inactive']);
    }

    /** UT-MST-013: thao tác master phải ghi audit — kiểm cho cả Department LẪN Shift */
    public function test_master_operations_are_audited(): void
    {
        $this->actingAs($this->admin)->postJson('/api/departments', [
            'department_code' => 'dept_sales', 'department_name' => '営業部',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'employee_id' => $this->admin->id, 'action' => 'department_created', 'target_type' => 'departments',
        ]);

        $this->actingAs($this->admin)->postJson('/api/shifts', [
            'shift_code' => 'shift_early', 'shift_name' => '早番',
            'start_time' => '07:00:00', 'end_time' => '16:00:00', 'break_minutes' => 60,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'shift_created', 'target_type' => 'shifts',
        ]);
    }
}
