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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id()->comment('シフトID');
            $table->string('shift_code', 50)->unique()->comment('シフトコード');
            $table->string('shift_name', 100)->comment('シフト名');
            $table->time('start_time')->comment('勤務開始時刻');
            $table->time('end_time')->comment('勤務終了時刻');
            $table->unsignedInteger('break_minutes')->default(0)->comment('休憩時間（分）'); // unsignedInteger, KHÔNG phải integer trần — không có số phút nghỉ âm
            $table->enum('status',['active', 'inactive'])->default('active')->index()->comment('状態');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
