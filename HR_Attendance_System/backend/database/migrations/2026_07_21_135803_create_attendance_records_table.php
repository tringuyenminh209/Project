<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id()->comment('勤怠記録ID');
            $table->foreignId('employee_id')->comment('社員ID')
                ->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('work_date')->comment('勤務日');
            $table->time('check_in_time')->nullable()->comment('出勤時刻');
            $table->time('check_out_time')->nullable()->comment('退勤時刻');

            // DECIMAL(5,2): chính xác tuyệt đối cho 8.00 giờ — KHÔNG dùng float (sai số nhị phân!)
            $table->decimal('work_hours', 5, 2)->nullable()->comment('勤務時間');

            // ★ Enum chỉ 3 giá trị — KHÔNG có NotCheckedIn.
            //   "Chưa chấm công" = KHÔNG TỒN TẠI record của ngày đó (quyết định thiết kế v1.2)
            $table->enum('status', ['CheckedIn', 'CheckedOut', 'Fixed'])->index()->comment('勤怠状態');
            $table->timestamps();

            // ★★ Unique kép — lá chắn cuối cùng chống double check-in (BR-001):
            //   dù code Service có bug, dù 2 request đến CÙNG LÚC (race condition),
            //   DB vẫn chỉ cho tồn tại 1 dòng / 1 người / 1 ngày.
            $table->unique(['employee_id', 'work_date'], 'uk_attendance_employee_work_date');
            $table->index('work_date', 'idx_attendance_work_date'); // cho query báo cáo theo tháng
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
