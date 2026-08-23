<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('yudisium_periods')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('invitation_categories')->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('yudisium_participants')->nullOnDelete();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('context_note')->nullable();
            $table->string('token')->unique();
            $table->string('rsvp_status')->default('pending');
            $table->text('rsvp_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'category_id']);
            $table->index(['period_id', 'rsvp_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_recipients');
    }
};
