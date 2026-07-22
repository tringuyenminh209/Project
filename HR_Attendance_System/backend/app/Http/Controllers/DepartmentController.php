<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateStatusRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService) {}

    // API-017 GET — điểm KHÁC Employee: có danh sách (phục vụ dropdown màn đăng ký社員).
    // Không FormRequest (GET không body), không Service (không nghiệp vụ — chỉ đọc, như session() ch04)
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => Department::orderBy('department_name')->get(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentService->create($request->user(), $request->validated());

        return response()->json([
            'success' => true, 'message' => '部署を登録しました。', 'data' => $department,
        ], 201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department = $this->departmentService->update($request->user(), $department, $request->validated());

        return response()->json([
            'success' => true, 'message' => '部署情報を更新しました。', 'data' => $department,
        ]);
    }

    public function setStatus(UpdateStatusRequest $request, Department $department): JsonResponse
    {
        $department = $this->departmentService->setStatus(
            $request->user(), $department, $request->validated('status'));

        return response()->json([
            'success' => true, 'message' => '部署の状態を更新しました。', 'data' => $department,
        ]);
    }
}
