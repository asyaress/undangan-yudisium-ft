<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->longText('rsvp_signature')->nullable()->after('rsvp_note');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->dropColumn('rsvp_signature');
        });
    }
};
