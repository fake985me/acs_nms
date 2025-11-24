<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ont_olt_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('device_id'); // References TR-069 device
            $table->foreignId('olt_id')->constrained()->onDelete('cascade');
            $table->string('pon_port')->nullable(); // e.g., "0/1/1"
            $table->integer('ont_id_on_port')->nullable(); // ONT ID on PON port
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('olt_id');
            $table->unique(['olt_id', 'pon_port', 'ont_id_on_port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ont_olt_assignments');
    }
};
