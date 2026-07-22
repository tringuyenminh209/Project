<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employeeService) {}

    // API-014
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => '社員を登録しました。',
            'data'    => $employee,
        ], 201); // 201 Created — đúng convention REST cho "tạo mới thành công" (khác 200 của login/update)
    }

    // API-015. $employee tự inject nhờ Route Model Binding (giải thích ở bước 5)
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee = $this->employeeService->update($request->user(), $employee, $request->validated());

        return response()->json([
            'success' => true,
            'message' => '社員情報を更新しました。',
            'data'    => $employee,
        ]);
    }

    // API-016
    public function setStatus(UpdateStatusRequest $request, Employee $employee): JsonResponse
    {
        $employee = $this->employeeService->setStatus($request->user(), $employee, $request->validated('status'));

        return response()->json([
            'success' => true,
            'message' => '社員の状態を更新しました。',
            'data'    => $employee,
        ]);
    }
}
