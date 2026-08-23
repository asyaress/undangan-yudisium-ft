<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_categories', function (Blueprint $table) {
            $table->foreignId('period_id')
                ->nullable()
                ->after('id')
                ->constrained('yudisium_periods')
                ->cascadeOnDelete();
        });

        $ownerPeriodId = DB::table('yudisium_periods')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->value('id')
            ?: DB::table('yudisium_periods')->orderBy('id')->value('id');

        if ($ownerPeriodId) {
            DB::table('invitation_categories')
                ->whereNull('period_id')
                ->update(['period_id' => $ownerPeriodId]);
        }

        Schema::table('invitation_categories', function (Blueprint $table) {
            $table->dropUnique('invitation_categories_slug_unique');
        });

        if ($ownerPeriodId) {
            $sourceCategories = DB::table('invitation_categories')
                ->where('period_id', $ownerPeriodId)
                ->orderBy('id')
                ->get();
            $periodIds = DB::table('yudisium_periods')
                ->where('id', '!=', $ownerPeriodId)
                ->pluck('id');
            $now = now();

            foreach ($periodIds as $periodId) {
                foreach ($sourceCategories as $category) {
                    $cloneId = DB::table('invitation_categories')->insertGetId([
                        'period_id' => $periodId,
                        'slug' => $category->slug,
                        'title' => $category->title,
                        'recipient_label' => $category->recipient_label,
                        'cover_text' => $category->cover_text,
                        'invitation_text' => $category->invitation_text,
                        'closing_text' => $category->closing_text,
                        'sort_order' => $category->sort_order,
                        'access_mode' => $category->access_mode ?? 'private',
                        'rsvp_enabled' => $category->rsvp_enabled ?? true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('invitation_recipients')
                        ->where('period_id', $periodId)
                        ->where('category_id', $category->id)
                        ->update(['category_id' => $cloneId]);
                }
            }
        }

        Schema::table('invitation_categories', function (Blueprint $table) {
            $table->unique(['period_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('invitation_categories', function (Blueprint $table) {
            $table->dropUnique(['period_id', 'slug']);
            $table->unique('slug');
            $table->dropConstrainedForeignId('period_id');
        });
    }
};
