<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('device_id'); // References TR-069 device
            $table->decimal('rx_power', 8, 2)->nullable(); // dBm
            $table->decimal('tx_power', 8, 2)->nullable(); // dBm
            $table->decimal('temperature', 8, 2)->nullable(); // Celsius
            $table->decimal('voltage', 8, 2)->nullable(); // Volts
            $table->decimal('ber_value', 15, 10)->nullable(); // Bit Error Rate
            $table->decimal('distance', 8, 2)->nullable(); // meters
            $table->timestamp('measured_at');
            $table->timestamps();
            
            $table->index('device_id');
            $table->index('measured_at');
            $table->index(['device_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_metrics');
    }
};
