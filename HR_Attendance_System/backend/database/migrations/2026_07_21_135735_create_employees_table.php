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
        Schema::create('employees', function (Blueprint $table) {
            $table->id()->comment('社員ID');
            $table->string('employee_id',50)->unique()->comment('社員番号');
            $table->string('name',100)->comment('氏名');
            $table->string('email',255)->unique()->comment('メールアドレス');
            $table->string('password_hash',255)->comment('ハッシュ化パスワード');

            // foreignId()->constrained() = tạo cột BIGINT UNSIGNED + FK tự trỏ về bảng tương ứng.
            // comment() phải đặt TRƯỚC constrained() — sau constrained() là đang cấu hình FK, không phải cột.
            $table->foreignId('role_id')->comment('権限ID')
                ->constrained()->cascadeOnUpdate()->restrictOnDelete(); // cấm xoá role đang có nhân viên dùng
            $table->foreignId('department_id')->comment('部署ID')
                ->constrained()->cascadeOnUpdate()->restrictOnDelete();

            // shift_id cho phép NULL (nhân viên có thể chưa gán ca) → nullable + nullOnDelete
            $table->foreignId('shift_id')->nullable()->comment('標準シフトID')
                ->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->index()->comment('状態');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
