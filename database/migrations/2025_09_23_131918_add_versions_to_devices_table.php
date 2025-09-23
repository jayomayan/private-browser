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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('arm_version')->nullable()->after('name');
            $table->string('stm32_version')->nullable()->after('arm_version');
            $table->string('web_version')->nullable()->after('stm32_version');
            $table->string('kernel_version')->nullable()->after('web_version');
            $table->string('mib_version')->nullable()->after('kernel_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'arm_version',
                'stm32_version',
                'web_version',
                'kernel_version',
                'mib_version',
            ]);
        });
    }
};
