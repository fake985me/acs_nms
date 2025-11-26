<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom 'role' kalau belum ada
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')
                    ->default('admin')
                    ->after('password'); // sesuaikan kalau kolomnya beda
            });
        }

        // Set semua user jadi admin (kecuali id 0 kalau ada)
        DB::table('users')
            ->where('id', '!=', 0)
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        // Kalau mau aman saat rollback, bisa hapus kolom role
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
