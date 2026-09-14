<?php

use App\Models\InvitationCategory;
use App\Models\YudisiumPeriod;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        YudisiumPeriod::query()
            ->get()
            ->filter(fn (YudisiumPeriod $period) => $period->isLegacyPeriodTwo())
            ->each(function (YudisiumPeriod $period): void {
                $period->forceFill([
                    'is_published' => false,
                    'is_active' => false,
                ])->save();
            });

        InvitationCategory::query()
            ->where('cover_text', 'like', '%Angkatan 82 Periode 2%')
            ->get()
            ->each(function (InvitationCategory $category): void {
                $category->cover_text = str_replace(
                    'Angkatan 82 Periode 2',
                    'Angkatan 83 Periode 3',
                    (string) $category->cover_text,
                );
                $category->save();
            });
    }

    public function down(): void
    {
        //
    }
};
