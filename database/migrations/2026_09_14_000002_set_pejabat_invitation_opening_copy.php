<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $text = 'Dengan hormat, kami mengundang Pejabat Fakultas dan Universitas Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.';

        $periodIds = DB::table('yudisium_periods')
            ->where(function ($query) {
                $query->where('period_label', 'like', '%Periode 3%')
                    ->orWhere('slug', 'like', '%periode-3%');
            })
            ->pluck('id');

        if ($periodIds->isEmpty()) {
            return;
        }

        DB::table('invitation_categories')
            ->whereIn('period_id', $periodIds)
            ->where('slug', 'pejabat')
            ->update(['invitation_text' => $text]);
    }

    public function down(): void
    {
        // Editorial copy; leave current invitation_text in place.
    }
};
