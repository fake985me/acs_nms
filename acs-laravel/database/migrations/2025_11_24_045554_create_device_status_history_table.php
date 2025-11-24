<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_status_history', function (Blueprint $table) {
            $table->id();
            $table->string('device_id'); // References TR-069 device
            $table->string('status'); // online, offline, error, etc.
            $table->boolean('is_online')->default(false);
            $table->string('reason')->nullable(); // Why status changed
            $table->text('details')->nullable(); // JSON or additional info
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('recorded_at');
            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_status_history');
    }
};
