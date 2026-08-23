<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->nullable()->constrained('yudisium_periods')->nullOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('yudisium_participants')->nullOnDelete();
            $table->string('nim', 30)->nullable();
            $table->string('status', 30)->default('accepted');
            $table->string('source', 30)->default('web');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance_meter')->nullable();
            $table->unsignedInteger('accuracy_meter')->nullable();
            $table->unsignedInteger('radius_meter')->nullable();
            $table->string('message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            $table->index(['period_id', 'status']);
            $table->index(['participant_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_logs');
    }
};
