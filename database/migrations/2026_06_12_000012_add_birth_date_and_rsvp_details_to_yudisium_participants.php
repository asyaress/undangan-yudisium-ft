<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('name');
            $table->unsignedSmallInteger('rsvp_companion_count')->nullable()->after('rsvp_note');
            $table->string('rsvp_whatsapp', 30)->nullable()->after('rsvp_companion_count');
            $table->string('rsvp_proof_code', 32)->nullable()->unique()->after('rsvp_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->dropUnique('yudisium_participants_rsvp_proof_code_unique');
            $table->dropColumn([
                'birth_date',
                'rsvp_companion_count',
                'rsvp_whatsapp',
                'rsvp_proof_code',
            ]);
        });
    }
};
