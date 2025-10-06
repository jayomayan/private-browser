<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (config('database.default') === 'sqlite') {
                $table->string('serial_number', 100)->nullable()->after('device_name');
            } else {
                $table->string('serial_number', 100)->unique()->after('device_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};
