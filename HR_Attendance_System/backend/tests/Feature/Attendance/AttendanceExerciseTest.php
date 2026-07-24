<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceExerciseTest extends TestCase
{
    use RefreshDatabase;

    private Employee $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = Employee::where('email', 'user@example.com')->first();
    }

    private function checkedIn(Employee $emp, string $date = '2026-07-13'): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id' => $emp->id, 'work_date' => $date,
            'check_in_time' => '09:00:00', 'status' => 'CheckedIn',
        ]);
    }

    /** UT-ATT-003: work_date rác → 422 E003 */
    public function test_check_in_with_invalid_date_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/attendance/check-in', [
            'work_date' => 'khong-phai-ngay', 'check_in_time' => '09:00:00',
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');
    }

    /** UT-ATT-004: không token → 401 E010 */
    public function test_check_in_requires_authentication(): void
    {
        $response = $this->postJson('/api/attendance/check-in', [
            'work_date' => '2026-07-13', 'check_in_time' => '09:00:00',
        ]);

        $response->assertStatus(401)->assertJsonPath('error.code', 'E010');
    }

    /** UT-ATT-007: checkout 2 lần → 409 E004 */
    public function test_double_check_out_is_rejected(): void
    {
        $record = $this->checkedIn($this->user);
        $record->update(['check_out_time' => '18:00:00', 'status' => 'CheckedOut']);

        $response = $this->actingAs($this->user)->postJson('/api/attendance/check-out', [
            'work_date' => '2026-07-13', 'check_out_time' => '19:00:00',
        ]);

        $response->assertStatus(409)->assertJsonPath('error.code', 'E004');
    }

    /** UT-ATT-008: giờ ra ≤ giờ vào → 422 E003 (check nằm ở Service — BR-ATT-004) */
    public function test_check_out_before_check_in_is_rejected(): void
    {
        $this->checkedIn($this->user); // vào 09:00

        $response = $this->actingAs($this->user)->postJson('/api/attendance/check-out', [
            'work_date' => '2026-07-13', 'check_out_time' => '08:00:00', // TRƯỚC giờ vào
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'E003');
    }

    /** UT-ATT-011: /attendance/me CHỈ trả dữ liệu của mình */
    public function test_me_returns_only_own_records(): void
    {
        $manager = Employee::where('email', 'manager@example.com')->first();
        $this->checkedIn($this->user, '2026-07-13');    // của mình
        $this->checkedIn($manager, '2026-07-13');        // của người khác

        $response = $this->actingAs($this->user)->getJson('/api/attendance/me');

        $response->assertOk();
        // paginate → dữ liệu nằm ở data.data
        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame($this->user->id, $response->json('data.data.0.employee_id'));
    }

    /** UT-ATT-014: User lọc employee_id NGƯỜI KHÁC → 403 E002 */
    public function test_user_cannot_search_other_employees(): void
    {
        $manager = Employee::where('email', 'manager@example.com')->first();

        $response = $this->actingAs($this->user)
            ->getJson("/api/attendance?employee_id={$manager->id}");

        $response->assertStatus(403)->assertJsonPath('error.code', 'E002');
    }

    /** IDOR fix: User không được xem work_hours của record NGƯỜI KHÁC bằng cách đổi ID trên URL */
    public function test_user_cannot_view_work_hours_of_other_employee(): void
    {
        $manager = Employee::where('email', 'manager@example.com')->first();
        $record  = $this->checkedIn($manager, '2026-07-13');

        $response = $this->actingAs($this->user)
            ->getJson("/api/attendance/{$record->id}/work-hours");

        $response->assertStatus(403)->assertJsonPath('error.code', 'E002');
    }

    /** Đối chứng: xem work_hours của CHÍNH MÌNH vẫn phải hoạt động bình thường */
    public function test_user_can_view_own_work_hours(): void
    {
        $record = $this->checkedIn($this->user, '2026-07-13');
        $record->update(['check_out_time' => '18:00:00', 'work_hours' => 8.00, 'status' => 'CheckedOut']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/attendance/{$record->id}/work-hours");

        $response->assertOk()->assertJsonPath('data.attendance_id', $record->id);
    }
}
