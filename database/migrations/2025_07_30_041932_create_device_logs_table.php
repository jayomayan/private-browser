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
       Schema::create('device_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->date('date');
            $table->time('time');
            $table->string('message')->nullable();
            $table->timestamps();
        });

        // Add index for faster searching by IP
        Schema::table('device_logs', function (Blueprint $table) {
            $table->index('ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
