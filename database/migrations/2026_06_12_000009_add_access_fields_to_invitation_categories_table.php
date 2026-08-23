<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_categories', function (Blueprint $table) {
            $table->string('access_mode', 20)->default('private')->after('sort_order');
            $table->boolean('rsvp_enabled')->default(true)->after('access_mode');
        });

        DB::table('invitation_categories')->where('slug', 'yudisiawan')->update([
            'access_mode' => 'nim',
            'rsvp_enabled' => true,
        ]);

        DB::table('invitation_categories')->whereIn('slug', ['orangtua', 'tendik'])->update([
            'access_mode' => 'private',
            'rsvp_enabled' => true,
        ]);

        DB::table('invitation_categories')->whereIn('slug', ['pejabat', 'ketuasenat', 'anggotasenat'])->update([
            'access_mode' => 'public',
            'rsvp_enabled' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('invitation_categories', function (Blueprint $table) {
            $table->dropColumn(['access_mode', 'rsvp_enabled']);
        });
    }
};
