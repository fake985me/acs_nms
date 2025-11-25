<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Set first user as superadmin
        $firstUser = DB::table('users')->orderBy('id')->first();
        if ($firstUser) {
            DB::table('users')
                ->where('id', $firstUser->id)
                ->update(['role' => 'super_admin']);
        }
        
        // Set all other users as admin
        DB::table('users')
            ->where('id', '!=', $firstUser ? $firstUser->id : 0)
            ->update(['role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all users to default 'user' role
        DB::table('users')->update(['role' => 'user']);
    }
};
