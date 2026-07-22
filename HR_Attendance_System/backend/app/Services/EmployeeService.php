<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class EmployeeService
{
    public function __construct(private readonly AuditLogService $auditLog){}

    // API-014. DB::transaction: employees INSERT + audit INSERT = 2 bảng
    // → cùng sống cùng chết (12_詳細設計書 mục 10 — phần "trả nợ" từ ch07 課題 3)
    // forceCreate() thay vì create(): role_id/department_id/shift_id/status/password_hash
    // KHÔNG nằm trong $fillable (chương 04, chống Privilege Escalation) — bypass CÓ CHỦ ĐÍCH
    // vì route đã qua role:admin middleware trước khi tới Service này.
    public function create(Employee $actor, array $data): Employee
    {
        return DB::transaction(function () use ($actor, $data) {
            $employee = Employee::forceCreate([
                'employee_id'   => $data['employee_id'],
                'name'          => $data['name'],
                'email'         => $data['email'],
                'password_hash' => Hash::make($data['password']), // KHÔNG BAO GIỜ lưu thô
                'role_id'       => $data['role_id'],
                'department_id' => $data['department_id'],
                'shift_id'      => $data['shift_id'] ?? null,
                'status'        => 'active',
            ]);

            $this->auditLog->record($actor, 'employee_created', 'employees', $employee->id);

            return $employee; // transaction() trả về giá trị return của closure
        });
    }

    // API-015 — forceFill() cùng lý do với create() ở trên
    public function update(Employee $actor, Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($actor, $employee, $data) {
            $employee->forceFill([
                'name'          => $data['name'],
                'email'         => $data['email'],
                'role_id'       => $data['role_id'],
                'department_id' => $data['department_id'],
                'shift_id'      => $data['shift_id'] ?? null,
            ])->save();

            $this->auditLog->record($actor, 'employee_updated', 'employees', $employee->id);

            return $employee;
        });
    }

    // API-016 — BR-EMP-001: KHÔNG xoá vật lý (phá FK của attendance/leave đã có)
    // forceFill(): status không nằm trong $fillable, xem lý do ở create() phía trên
    public function setStatus(Employee $actor, Employee $employee, string $status): Employee
    {
        return DB::transaction(function () use ($actor, $employee, $status) {
            $employee->forceFill(['status' => $status])->save();

            $this->auditLog->record($actor, 'employee_status_changed', 'employees', $employee->id);

            return $employee;
        });
    }

}
