<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_periods', function (Blueprint $table) {
            $table->string('event_time')->nullable()->after('event_date');
            $table->string('address')->nullable()->after('location');
            $table->json('agenda_items')->nullable()->after('address');
            $table->json('event_notes')->nullable()->after('agenda_items');
            $table->string('signature_city')->nullable()->after('event_notes');
            $table->string('signer_name')->nullable()->after('signature_city');
            $table->string('signer_title')->nullable()->after('signer_name');
        });
    }

    public function down(): void
    {
        Schema::table('yudisium_periods', function (Blueprint $table) {
            $table->dropColumn([
                'event_time',
                'address',
                'agenda_items',
                'event_notes',
                'signature_city',
                'signer_name',
                'signer_title',
            ]);
        });
    }
};
