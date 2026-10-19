<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'Staff Gudang'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'Customer')->update(['role' => 'Staff Gudang']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('Admin', 'Staff Gudang', 'Manajer Gudang') NOT NULL DEFAULT 'Staff Gudang'");
        }
    }
};
