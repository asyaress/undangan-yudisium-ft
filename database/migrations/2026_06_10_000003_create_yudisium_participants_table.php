<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yudisium_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->nullable()->constrained('yudisium_periods')->nullOnDelete();
            $table->string('nim')->unique();
            $table->string('name');
            $table->string('study_program')->nullable();
            $table->string('faculty')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('checkin_status')->default('pending');
            $table->timestamp('checked_in_at')->nullable();
            $table->string('checkin_source')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'checkin_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yudisium_participants');
    }
};
