<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    // API-004
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $record = $this->attendanceService->checkIn(
            $request->user(),
            $request->validated('work_date'),
            $request->validated('check_in_time'),
        );

        return response()->json([
            'success' => true, 'message' => '出勤打刻を登録しました。', 'data' => $record,
        ], 201);
    }

    // API-005
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $record = $this->attendanceService->checkOut(
            $request->user(),
            $request->validated('work_date'),
            $request->validated('check_out_time'),
        );

        return response()->json([
            'success' => true, 'message' => '退勤打刻を登録しました。', 'data' => $record,
        ]);
    }

    // API-006（課題）— Route Model Binding load record, E007 tự động nếu ID ma
    public function workHours(Request $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        // IDOR fix: Binding chỉ tìm ĐÚNG ID, không tự biết record có thuộc tầm nhìn
        // của người gọi không — PHẢI tự kiểm tra (xem AttendanceService::checkAccessToRecord).
        $this->attendanceService->checkAccessToRecord($request->user(), $attendanceRecord);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => [
                'attendance_id' => $attendanceRecord->id,
                'work_hours'    => $attendanceRecord->work_hours, // giá trị SNAPSHOT đã chốt
                // break_minutes chỉ là THAM KHẢO shift hiện tại — không dùng tính lại (10_API設計 6.6 v1.1)
                'break_minutes' => $attendanceRecord->employee->shift?->break_minutes ?? 0,
            ],
        ]);
    }

    // API-007（課題）
    public function me(Request $request): JsonResponse
    {
        $records = $this->attendanceService->myAttendance(
            $request->user(),
            $request->only(['target_month', 'from_date', 'to_date']), // chỉ lấy đúng key cần
        );

        return response()->json(['success' => true, 'message' => 'OK', 'data' => $records]);
    }

    // API-008（課題）
    public function search(Request $request): JsonResponse
    {
        $records = $this->attendanceService->search(
            $request->user(),
            $request->only(['employee_id', 'department_id', 'from_date', 'to_date', 'status']),
        );

        return response()->json(['success' => true, 'message' => 'OK', 'data' => $records]);
    }
}
