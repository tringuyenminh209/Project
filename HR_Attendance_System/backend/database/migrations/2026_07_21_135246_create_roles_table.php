<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // id() = BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY — khớp TBL-001 dòng 1
            $table->id()->comment('権限ID');

            // string('...', 50) = VARCHAR(50). unique() = ràng buộc UK trong 09_テーブル定義
            $table->string('role_code', 50)->unique()->comment('権限コード');
            $table->string('role_name', 100)->comment('権限名');

            // ENUM đúng theo 09 mục 7 (Enum定義): active / inactive
            $table->enum('status',['active', 'inactive'])->default('active')->index()->comment('状態');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
