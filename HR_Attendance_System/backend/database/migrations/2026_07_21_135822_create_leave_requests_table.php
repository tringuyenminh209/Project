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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id()->comment('休暇申請ID');
            $table->foreignId('employee_id')->comment('申請社員ID')
                ->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('leave_type', ['paid_leave', 'absence', 'late', 'early_leave'])->index()->comment('申請種別');
            $table->date('start_date')->index()->comment('開始日');
            $table->date('end_date')->comment('終了日');
            $table->string('reason', 500)->comment('申請理由');

            // ★ 3 trạng thái — KHÔNG có Completed (quyết định v1.1: "đã qua ngày" tính lúc hiển thị)
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending')->comment('申請状態');

            // approved_by cũng trỏ về employees — 2 FK cùng trỏ 1 bảng!
            // foreignId('approved_by') không đoán được tên bảng → phải chỉ rõ constrained('employees')
            $table->foreignId('approved_by')->nullable()->comment('承認者ID')
                ->constrained('employees')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->comment('承認日時');

            // 10_API設計 6.11のcommentフィールド — 当初09に列がなく、ApprovalRequestでvalidateされても
            // 保存先が存在しないギャップだった（仕様確認の末、列を追加する方針B）
            $table->string('comment', 500)->nullable()->comment('承認/却下コメント（Manager/Adminが入力）');

            $table->timestamps();

            $table->index(['employee_id', 'status'], 'idx_leave_employee_status');
            $table->index(['status', 'start_date'], 'idx_leave_status_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
