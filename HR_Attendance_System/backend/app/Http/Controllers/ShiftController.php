<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;

class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shiftService) {}

    // API-018 GET — y hệt lý do của DepartmentController::index (phục vụ dropdown
    // màn đăng ký/sửa社員 chọn ca làm việc)
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => Shift::orderBy('start_time')->get(),
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->create($request->user(), $request->validated());

        return response()->json([
            'success' => true, 'message' => 'シフトを登録しました。', 'data' => $shift,
        ], 201);
    }

    // $shift tự inject qua Route Model Binding ({shift} ↔ $shift, giống {department}/{employee})
    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $shift = $this->shiftService->update($request->user(), $shift, $request->validated());

        return response()->json([
            'success' => true, 'message' => 'シフト情報を更新しました。', 'data' => $shift,
        ]);
    }

    public function setStatus(UpdateStatusRequest $request, Shift $shift): JsonResponse
    {
        $shift = $this->shiftService->setStatus(
            $request->user(), $shift, $request->validated('status'));

        return response()->json([
            'success' => true, 'message' => 'シフトの状態を更新しました。', 'data' => $shift,
        ]);
    }
}
