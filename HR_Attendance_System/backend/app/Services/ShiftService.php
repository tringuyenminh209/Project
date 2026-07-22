<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function create(Employee $actor, array $data): Shift
    {
        return DB::transaction(function () use ($actor, $data) {
            $shift = Shift::create([
                'shift_code'    => $data['shift_code'],
                'shift_name'    => $data['shift_name'],
                'start_time'    => $data['start_time'],
                'end_time'      => $data['end_time'],
                'break_minutes' => $data['break_minutes'],
                'status'        => 'active',
            ]);
            $this->auditLog->record($actor, 'shift_created', 'shifts', $shift->id);
            return $shift;
        });
    }

    public function update(Employee $actor, Shift $shift, array $data): Shift
    {
        return DB::transaction(function () use ($actor, $shift, $data) {
            $shift->update([
                'shift_name'    => $data['shift_name'],
                'start_time'    => $data['start_time'],
                'end_time'      => $data['end_time'],
                'break_minutes' => $data['break_minutes'],
            ]);
            $this->auditLog->record($actor, 'shift_updated', 'shifts', $shift->id);
            return $shift;
        });
    }

    public function setStatus(Employee $actor, Shift $shift, string $status): Shift
    {
        return DB::transaction(function () use ($actor, $shift, $status) {
            $shift->update(['status' => $status]);
            $this->auditLog->record($actor, 'shift_status_changed', 'shifts', $shift->id);
            return $shift;
        });
    }
}
