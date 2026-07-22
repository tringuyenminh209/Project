<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

// Y hệt khuôn EmployeeService — chỉ khác model + tên action.
// Nhận ra "y hệt khuôn" chính là mục tiêu học của chương 05.
class DepartmentService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function create(Employee $actor, array $data): Department
    {
        return DB::transaction(function () use ($actor, $data) {
            $department = Department::create([
                'department_code' => $data['department_code'],
                'department_name' => $data['department_name'],
                'status'          => 'active',
            ]);
            $this->auditLog->record($actor, 'department_created', 'departments', $department->id);
            return $department;
        });
    }

    public function update(Employee $actor, Department $department, array $data): Department
    {
        return DB::transaction(function () use ($actor, $department, $data) {
            $department->update([
                'department_code' => $data['department_code'],
                'department_name' => $data['department_name'],
            ]);
            $this->auditLog->record($actor, 'department_updated', 'departments', $department->id);
            return $department;
        });
    }

    // ER-003 無効化優先: không có method delete() — cố tình!
    public function setStatus(Employee $actor, Department $department, string $status): Department
    {
        return DB::transaction(function () use ($actor, $department, $status) {
            $department->update(['status' => $status]);
            $this->auditLog->record($actor, 'department_status_changed', 'departments', $department->id);
            return $department;
        });
    }
}
