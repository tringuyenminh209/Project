<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model勤怠記録 — bảng attendance_records (09_テーブル定義 4.5)
 *
 * 1 dòng = 1 người × 1 ngày làm việc (unique kép chống double check-in ở tầng DB).
 * Lưu ý thiết kế v1.2: KHÔNG có trạng thái NotCheckedIn —
 * "chưa chấm công" = không tồn tại record của ngày đó.
 */
class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id', 'work_date', 'check_in_time', 'check_out_time',
        'work_hours', 'status',
    ];

    /**
     * DB trả mọi thứ về string → cast sang kiểu dùng được:
     * - work_date  → object ngày (so sánh isToday()... được, chương 06 cần)
     * - work_hours → decimal đúng 2 chữ số — PHẢI ghi rõ ':2',
     *   viết 'decimal' trần là lỗi runtime khi đọc thuộc tính!
     */
    protected function casts(): array
    {
        return [
            'work_date'  => 'date',
            'work_hours' => 'decimal:2',
        ];
    }

    /** REL-004: bản ghi chấm công thuộc về 1 nhân viên（FK employee_id ở bảng này → belongsTo） */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
