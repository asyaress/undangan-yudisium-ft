<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkin_logs', function (Blueprint $table) {
            $table->foreignId('admin_id')
                ->nullable()
                ->after('participant_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('manual_note')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('checkin_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_id');
            $table->dropColumn('manual_note');
        });
    }
};
