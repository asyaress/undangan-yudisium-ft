<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->index(['period_id', 'nim'], 'participants_period_nim_index');
            $table->index(['period_id', 'invitation_token'], 'participants_period_token_index');
        });
    }

    public function down(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->dropIndex('participants_period_nim_index');
            $table->dropIndex('participants_period_token_index');
        });
    }
};
