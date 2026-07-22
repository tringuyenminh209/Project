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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id()->comment('操作ログID');

            // 操作者ID: nullable — log của xử lý System không gắn với nhân viên nào.
            $table->foreignId('employee_id')->nullable()->comment('操作者ID')
                ->constrained()->cascadeOnUpdate()->nullOnDelete();

            $table->string('action', 100)->index()->comment('操作内容');

            // target_type / target_id: nullable — ví dụ CSV出力 log không có record đối tượng cụ thể
            $table->string('target_type', 100)->nullable()->comment('操作対象種別');
            $table->unsignedBigInteger('target_id')->nullable()->comment('操作対象ID');

            $table->enum('result', ['success', 'failure'])->default('success')->index()->comment('操作結果');
            $table->string('ip_address', 45)->nullable()->comment('IPアドレス');

            // Log chỉ có created_at, KHÔNG có updated_at — log ghi 1 lần, không bao giờ sửa
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');

            $table->index(['employee_id', 'created_at'], 'idx_audit_employee_created');
            $table->index(['target_type', 'target_id'], 'idx_audit_target');
            $table->index('created_at', 'idx_audit_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
