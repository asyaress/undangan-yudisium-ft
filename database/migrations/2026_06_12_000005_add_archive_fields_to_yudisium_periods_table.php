<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_periods', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->unsignedSmallInteger('event_year')->nullable()->after('slug');
            $table->string('cohort_label')->nullable()->after('event_year');
            $table->string('period_label')->nullable()->after('cohort_label');
            $table->timestamp('rsvp_deadline')->nullable()->after('location');
            $table->boolean('is_published')->default(true)->after('is_active');
        });

        DB::table('yudisium_periods')
            ->select(['id', 'name', 'event_date'])
            ->orderBy('id')
            ->get()
            ->each(function (object $period): void {
                $slugBase = Str::slug($period->name ?: 'yudisium');
                $slug = $slugBase !== '' ? $slugBase : 'yudisium';

                DB::table('yudisium_periods')
                    ->where('id', $period->id)
                    ->update([
                        'slug' => $slug.'-'.$period->id,
                        'event_year' => $period->event_date ? (int) date('Y', strtotime((string) $period->event_date)) : null,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('yudisium_periods', function (Blueprint $table) {
            $table->dropUnique('yudisium_periods_slug_unique');
            $table->dropColumn([
                'slug',
                'event_year',
                'cohort_label',
                'period_label',
                'rsvp_deadline',
                'is_published',
            ]);
        });
    }
};
