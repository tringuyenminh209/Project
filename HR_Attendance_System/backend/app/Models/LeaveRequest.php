<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model休暇申請 — bảng leave_requests (09_テーブル定義 4.6)
 *
 * Trạng thái chỉ có Pending / Approved / Rejected (thiết kế v1.1):
 * "đơn đã qua ngày (済)" KHÔNG lưu DB mà tính lúc hiển thị — xem isCompletedDisplay().
 */
class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type', 'start_date', 'end_date',
        'reason', 'status', 'approved_by', 'approved_at', 'comment',
    ];

    /** Cast string → object ngày tháng để so sánh được (isPast()...) */
    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'approved_at' => 'datetime',
        ];
    }

    // ================= Relations =================

    /** REL-005: người NỘP đơn（FK employee_id — tên chuẩn nên Laravel tự đoán được） */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * REL-007: người DUYỆT đơn — cùng trỏ employees nhưng qua FK approved_by.
     * Laravel đoán FK theo tên method（approver → "approver_id"）→ SAI,
     * nên phải chỉ rõ tên cột ở tham số 2.
     * Đơn Pending chưa ai duyệt → approved_by NULL → gọi bằng $req->approver?->name
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    // ========== Hỗ trợ hiển thị「済」（12_詳細設計書 5.7） ==========

    /** Đơn đã duyệt VÀ đã qua ngày cuối → màn hình一覧 gắn thêm nhãn「済」 */
    public function isCompletedDisplay(): bool
    {
        return $this->status === 'Approved' && $this->end_date->isPast();
    }

    /** Scope lọc các đơn Approved đã qua hạn（12_詳細設計書 mục 6: scopeIsOverdue） */
    public function scopeIsOverdue(Builder $query): Builder
    {
        return $query->where('status', 'Approved')
            ->whereDate('end_date', '<', today());
    }
}
