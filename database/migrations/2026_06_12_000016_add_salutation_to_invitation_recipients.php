<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->string('salutation', 30)->nullable()->after('participant_id');
        });
    }

    public function down(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->dropColumn('salutation');
        });
    }
};
