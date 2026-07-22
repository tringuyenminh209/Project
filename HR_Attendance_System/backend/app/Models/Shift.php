<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelシフト — bảng shifts (09_テーブル定義 4.3)
 * Bảng master ca làm việc. break_minutes của ca sẽ được AttendanceService dùng
 * để tính work_hours lúc退勤 (BR-ATT-005) — lấy giá trị TẠI THỜI ĐIỂM ĐÓ,
 * không tính lại khi ca đổi sau này (スナップショット方針).
 */
class Shift extends Model
{
    protected $fillable = [
        'shift_code', 'shift_name', 'start_time', 'end_time',
        'break_minutes', 'status',
    ];

    /** REL-003 chiều ngược: 1 ca — N nhân viên dùng làm ca mặc định */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
