<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_logs', function (Blueprint $table) {
            $table->uuid('client_scan_id')->nullable()->unique();
            $table->string('device_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('checkin_logs', function (Blueprint $table) {
            $table->dropUnique(['client_scan_id']);
            $table->dropColumn(['client_scan_id', 'device_name']);
        });
    }
};
