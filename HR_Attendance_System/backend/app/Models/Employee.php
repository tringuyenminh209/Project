<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model DUY NHẤT extends Authenticatable (= Model + khả năng đăng nhập):
 * hệ auth của Laravel chỉ chấp nhận object có contract này.
 * 6 model còn lại (Role/Department/Shift/AttendanceRecord/LeaveRequest/AuditLog) extends Model thường.
 */
class Employee extends Authenticatable
{
    use HasApiTokens; // trait của Sanctum → có createToken() / currentAccessToken() / tokens()

    /**
     * Cột ĐƯỢC PHÉP gán hàng loạt qua create()/update()/fill() — chỉ 3 field hồ sơ thường.
     * role_id/department_id/shift_id/status/password_hash CỐ TÌNH không nằm trong danh sách này
     * (Mass Assignment / Privilege Escalation, 14_セキュリティ設計 8.1): nếu 1 field đặc quyền
     * lọt vào $fillable, chỉ cần 1 chỗ code sau này lỡ gọi Employee::create($request->all())
     * là user tự thăng mình lên admin qua "role_id":3 trong payload. EmployeeService set các
     * field đặc quyền qua forceFill()/forceCreate() — bypass có chủ đích, chỉ dùng ở code đã
     * qua role:admin middleware.
     */
    protected $fillable = ['employee_id', 'name', 'email'];

    /** Cột KHÔNG BAO GIỜ xuất hiện trong JSON response (UT-AUTH-009 test đúng điều này). */
    protected $hidden = ['password_hash'];

    /** Hệ auth mặc định tìm cột "password" — テーブル定義 đặt tên password_hash nên phải khai lại. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    // ================= Relations（08_ER図） =================
    // Quy tắc chiều: FK nằm ở bảng MÌNH → belongsTo / FK ở bảng KIA trỏ về mình → hasMany

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
