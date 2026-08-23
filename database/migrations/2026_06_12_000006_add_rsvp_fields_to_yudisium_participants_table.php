<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->string('invitation_token')->nullable()->unique()->after('phone');
            $table->string('rsvp_status')->default('pending')->after('checkin_source');
            $table->text('rsvp_note')->nullable()->after('rsvp_status');
            $table->timestamp('rsvp_responded_at')->nullable()->after('rsvp_note');
        });

        DB::table('yudisium_participants')
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $participant): void {
                DB::table('yudisium_participants')
                    ->where('id', $participant->id)
                    ->update([
                        'invitation_token' => Str::lower(Str::random(24)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->dropUnique('yudisium_participants_invitation_token_unique');
            $table->dropColumn([
                'invitation_token',
                'rsvp_status',
                'rsvp_note',
                'rsvp_responded_at',
            ]);
        });
    }
};
