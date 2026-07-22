<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * ★ QUAN TRỌNG: tắt quản lý timestamp tự động của Eloquent.
     * Mặc định Model luôn ghi cả created_at LẪN updated_at khi INSERT —
     * bảng này không có cột updated_at → không tắt là nổ
     * "Unknown column 'updated_at'" ngay lần ghi log đầu tiên.
     * created_at để DB tự điền (useCurrent trong migration).
     */
    public $timestamps = false;

    protected $fillable = [
        'employee_id', 'action', 'target_type', 'target_id', 'result', 'ip_address'
    ];

    /** Đọc created_at ra object thời gian（$timestamps=false nên phải tự khai cast） */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * REL-006: log thuộc về 1 người thao tác.
     * employee_id nullable（log của xử lý System）→ gọi bằng $log->employee?->name
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
