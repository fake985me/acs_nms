<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('snmp_port')->default(161);
            $table->string('snmp_version')->default('2c'); // 2c or 3
            $table->string('snmp_community')->nullable();
            $table->string('snmp_v3_username')->nullable();
            $table->string('snmp_v3_auth_type')->nullable(); // SHA1, MD5
            $table->string('snmp_v3_auth_password')->nullable();
            $table->string('snmp_v3_priv_type')->nullable(); // DES, AES
            $table->string('snmp_v3_priv_password')->nullable();
            $table->integer('snmp_timeout')->default(6000);
            $table->integer('web_management_port')->default(80);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('ip_address');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olts');
    }
};
