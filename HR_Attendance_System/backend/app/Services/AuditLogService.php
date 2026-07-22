<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class AuditLogService
{
    /**
     * 12_詳細設計書 5.12 — ghi 1 dòng vào audit_logs.
     * $targetType/$targetId nullable vì có thao tác không gắn với 1 record cụ thể
     * (ví dụ CSV出力 ở chương 08 — xuất cả tháng, không phải 1 record nào).
     */
    public function record(
        Employee $actor,
        string $action,
        ?string $targetType = null,
        ?int  $targetId = null,
        string $result = 'success'
    ): void {
        AuditLog::create([
            'employee_id' => $actor->id,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'result'      => $result,
            'ip_address'  => request()->ip(),
        ]);
    }
}
