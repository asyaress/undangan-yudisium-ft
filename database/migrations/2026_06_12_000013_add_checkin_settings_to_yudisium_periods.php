<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_periods', function (Blueprint $table) {
            $table->timestamp('checkin_opens_at')->nullable()->after('rsvp_deadline');
            $table->timestamp('checkin_closes_at')->nullable()->after('checkin_opens_at');
            $table->decimal('checkin_latitude', 10, 7)->nullable()->after('checkin_closes_at');
            $table->decimal('checkin_longitude', 10, 7)->nullable()->after('checkin_latitude');
            $table->unsignedSmallInteger('checkin_radius_meter')->default(300)->after('checkin_longitude');
            $table->boolean('checkin_location_required')->default(true)->after('checkin_radius_meter');
        });
    }

    public function down(): void
    {
        Schema::table('yudisium_periods', function (Blueprint $table) {
            $table->dropColumn([
                'checkin_opens_at',
                'checkin_closes_at',
                'checkin_latitude',
                'checkin_longitude',
                'checkin_radius_meter',
                'checkin_location_required',
            ]);
        });
    }
};
