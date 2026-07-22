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
        Schema::create('departments', function (Blueprint $table) {
            $table->id()->comment('部署ID');
            $table->string('department_code',50)->unique()->comment('部署コード');
            $table->string('department_name',100)->unique()->comment('部署名'); // UK cả 2 cột
            $table->enum('status',['active', 'inactive'])->default('active')->index()->comment('状態');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
