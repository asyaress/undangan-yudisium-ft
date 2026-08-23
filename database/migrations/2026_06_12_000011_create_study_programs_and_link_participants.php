<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->unsignedInteger('sequence_number')->nullable()->after('period_id');
            $table->foreignId('study_program_id')
                ->nullable()
                ->after('sequence_number')
                ->constrained('study_programs')
                ->nullOnDelete();

            $table->index(['period_id', 'study_program_id', 'sequence_number'], 'participants_period_prodi_sequence_index');
        });
    }

    public function down(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            $table->dropIndex('participants_period_prodi_sequence_index');
            $table->dropConstrainedForeignId('study_program_id');
            $table->dropColumn('sequence_number');
        });

        Schema::dropIfExists('study_programs');
    }
};
