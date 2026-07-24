<?php

namespace App\Services;

use App\Exceptions\CheckOutWithoutCheckInException;
use App\Exceptions\DuplicateAttendanceException;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /** API-004 — 12_詳細設計書 5.2. Chống trùng 2 LỚP. */
    public function checkin(Employee $employee, string $workDate, string $checkInTime): AttendanceRecord
    {
        // LỚP 1 (BR-001): check trước — lỗi đẹp cho 99.9% trường hợp
        // whereDate() KHÔNG PHẢI where(): work_date cast 'date' của Eloquent luôn serialize
        // theo format datetime đầy đủ (Y-m-d H:i:s) khi ghi xuống DB. MySQL (cột kiểu DATE thật)
        // tự cắt bỏ phần giờ, nhưng SQLite (DB dùng khi php artisan test) không ép kiểu nên lưu
        // nguyên "...00:00:00" — where() so chuỗi tuyệt đối sẽ fail trên SQLite dù chạy đúng
        // trên MySQL thật (bẫy kinh điển: code chạy ngon qua Postman nhưng test vẫn đỏ).
        // whereDate() luôn chỉ so phần ngày, an toàn với cả 2 engine.
        $exits = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)->exists();

        if($exits){
            throw new DuplicateAttendanceException();
        }

        // LỚP 2: race condition (2 request cùng lúc) → unique kép của DB chặn nốt
        try {
            return AttendanceRecord::create([
                'employee_id'   => $employee->id,
                'work_date'     => $workDate,
                'check_in_time' => $checkInTime,
                'status'        => 'CheckedIn',
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateAttendanceException();
        }
    }

    /** API-005 — 12_詳細設計書 5.3. Công thức BR-ATT-005. */
    public function checkout(Employee $employee, string $workDate, string $checkOutTime): AttendanceRecord
    {
        // whereDate() cùng lý do như checkIn() ở trên.
        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $workDate)->first();

        if ($record === null) {
            throw new CheckOutWithoutCheckInException(); // E005 (BR-ATT-003)
        }

        if ($record->status !== 'CheckedIn') {
            throw new DuplicateAttendanceException(); // đã CheckedOut/Fixed → E004
        }

        // BR-ATT-004: so với DB → thuộc Service, không thuộc FormRequest
        $in  = Carbon::createFromFormat('H:i:s', $record->check_in_time);
        $out = Carbon::createFromFormat('H:i:s', $checkOutTime);

        if ($out->lessThanOrEqualTo($in)) {
            throw ValidationException::withMessages([
                'check_out_time' => ['退勤時刻は出勤時刻より後にしてください。'],
            ]);
        }

        // BR-ATT-005: snapshot break_minutes TẠI thời điểm này (sau đổi shift KHÔNG tính lại)
        $breakMinutes = $employee->shift?->break_minutes ?? 0;
        $workMinutes  = max(0, $in->diffInMinutes($out) - $breakMinutes);

        $record->update([
            'check_out_time' => $checkOutTime,
            'work_hours'     => round($workMinutes / 60, 2),
            'status'         => 'CheckedOut',
        ]);

        return $record->fresh();
    }

    /**
     * IDOR fix — API-006 dùng Route Model Binding để load record theo ID, nhưng Binding
     * CHỈ tìm đúng ID, KHÔNG tự biết record đó có "thuộc tầm nhìn" của người gọi hay không.
     * Nếu thiếu hàm này: User A đổi số ID trên URL là xem được work_hours của User B ngay
     * (lỗ hổng IDOR — Insecure Direct Object Reference, bị security review tự động bắt được
     * trong dự án thật). Dùng lại đúng luật phân quyền của search() để 2 API nhất quán.
     */
    public function checkAccessToRecord(Employee $viewer, AttendanceRecord $record): void
    {
        $role = $viewer->role->role_code;

        if ($role === 'admin') {
            return; // Admin: không giới hạn
        }

        if ($role === 'manager') {
            if ($record->employee->department_id === $viewer->department_id) {
                return;
            }
            throw new AuthorizationException();
        }

        // user: chỉ được xem record của chính mình
        if ($record->employee_id !== $viewer->id) {
            throw new AuthorizationException();
        }
    }

    /** API-007（課題）— lịch sử CỦA MÌNH, lọc tháng/kỳ, phân trang */
    public function myAttendance(Employee $employee, array $filters): LengthAwarePaginator
    {
        return AttendanceRecord::where('employee_id', $employee->id)
            // when(): chỉ thêm điều kiện KHI filter tồn tại — chuỗi if gọn của Query Builder
            ->when($filters['target_month'] ?? null,
                fn ($q, $m) => $q->where('work_date', 'like', $m.'%'))
            // '2026-07%' khớp cả tháng — LIKE prefix an toàn với cả 2 engine, không cần whereDate()
            // whereDate() (không phải where()) cho from_date/to_date: cùng lý do checkIn() ở trên.
            // '<=' so chuỗi trực tiếp sẽ loại NHẦM đúng ngày biên trên SQLite (to_date trùng
            // ngày của record bị coi là "lớn hơn" vì có đuôi " 00:00:00").
            ->when($filters['from_date'] ?? null, fn ($q, $d) => $q->whereDate('work_date', '>=', $d))
            ->when($filters['to_date'] ?? null,   fn ($q, $d) => $q->whereDate('work_date', '<=', $d))
            ->orderByDesc('work_date')
            ->paginate(20);
    }

    /** API-008（課題）— 12_詳細設計書 5.4: phân quyền theo TẦM NHÌN dữ liệu */
    public function search(Employee $viewer, array $filters): LengthAwarePaginator
    {
        $query = AttendanceRecord::with('employee')->orderByDesc('work_date');
        $role  = $viewer->role->role_code;

        if ($role === 'user') {
            // UT-ATT-014: cố tình chỉ định người khác → E002 (AuthorizationException
            // được bootstrap map thành 403 E002 — thêm ở ch06 bước 6)
            if (! empty($filters['employee_id']) && (int) $filters['employee_id'] !== $viewer->id) {
                throw new AuthorizationException();
            }
            $query->where('employee_id', $viewer->id);

        } elseif ($role === 'manager') {
            // lọc sang phòng KHÁC phòng mình → E002 (16_結合試験 IT-ATT-006)
            if (! empty($filters['department_id'])
                && (int) $filters['department_id'] !== $viewer->department_id) {
                throw new AuthorizationException();
            }
            // mặc định: chỉ nhân viên phòng mình — whereHas = điều kiện trên bảng QUAN HỆ
            $query->whereHas('employee',
                fn ($q) => $q->where('department_id', $viewer->department_id));

            if (! empty($filters['employee_id'])) {
                // giao với phòng mình sẵn rồi → người khác phòng tự ra 0 kết quả
                $query->where('employee_id', $filters['employee_id']);
            }

        } else { // admin — không giới hạn, áp filter thô
            $query->when($filters['employee_id'] ?? null,   fn ($q, $v) => $q->where('employee_id', $v))
                ->when($filters['department_id'] ?? null,
                    fn ($q, $v) => $q->whereHas('employee', fn ($e) => $e->where('department_id', $v)));
        }

        return $query
            ->when($filters['from_date'] ?? null, fn ($q, $d) => $q->whereDate('work_date', '>=', $d))
            ->when($filters['to_date'] ?? null,   fn ($q, $d) => $q->whereDate('work_date', '<=', $d))
            ->when($filters['status'] ?? null,    fn ($q, $s) => $q->where('status', $s))
            ->paginate(20);
    }

}
