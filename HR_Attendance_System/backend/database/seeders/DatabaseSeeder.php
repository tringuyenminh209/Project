<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ----- 8.1 roles -----
        DB::table('roles')->insert([
            ['role_code' => 'user',    'role_name' => 'User',    'created_at' => now(), 'updated_at' => now()],
            ['role_code' => 'manager', 'role_name' => 'Manager', 'created_at' => now(), 'updated_at' => now()],
            ['role_code' => 'admin',   'role_name' => 'Admin',   'created_at' => now(), 'updated_at' => now()],
        ]);

        // ----- 8.2 departments -----
        DB::table('departments')->insert([
            ['department_code' => 'dept_admin', 'department_name' => '管理部', 'created_at' => now(), 'updated_at' => now()],
            ['department_code' => 'dept_dev',   'department_name' => '開発部', 'created_at' => now(), 'updated_at' => now()],
            ['department_code' => 'dept_hr',    'department_name' => '人事部', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ----- 8.3 shifts (09:00-18:00, nghỉ 60 phút — dùng để test work_hours=8.00) -----
        DB::table('shifts')->insert([
            ['shift_code' => 'shift_normal', 'shift_name' => '標準勤務',
                'start_time' => '09:00:00', 'end_time' => '18:00:00', 'break_minutes' => 60,
                'created_at' => now(), 'updated_at' => now()],
        ]);

        // ----- Nhân viên test — khớp TD-USER-001〜004 trong 15_単体試験仕様書 5.1 -----
        // Hash::make = bcrypt. TUYỆT ĐỐI không lưu password thô (SEC-POL-004)
        DB::table('employees')->insert([
            ['employee_id' => 'EMP001', 'name' => '一般 太郎', 'email' => 'user@example.com',
                'password_hash' => Hash::make('password123'), 'role_id' => 1, 'department_id' => 2, 'shift_id' => 1,
                'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['employee_id' => 'EMP002', 'name' => '管理 次郎', 'email' => 'manager@example.com',
                'password_hash' => Hash::make('password123'), 'role_id' => 2, 'department_id' => 2, 'shift_id' => 1,
                'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['employee_id' => 'EMP003', 'name' => 'システム 三郎', 'email' => 'admin@example.com',
                'password_hash' => Hash::make('password123'), 'role_id' => 3, 'department_id' => 1, 'shift_id' => 1,
                'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['employee_id' => 'EMP004', 'name' => '無効 四郎', 'email' => 'inactive@example.com',
                'password_hash' => Hash::make('password123'), 'role_id' => 1, 'department_id' => 2, 'shift_id' => 1,
                'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
