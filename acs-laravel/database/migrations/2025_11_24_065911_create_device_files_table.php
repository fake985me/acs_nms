<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_type'); // firmware, config, vendor_log
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable();
            $table->string('version')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('file_type');
            $table->index('manufacturer');
            $table->index('model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_files');
    }
};
