<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invitation_categories')
            ->where('slug', 'ketua-senat')
            ->update(['slug' => 'ketuasenat']);

        DB::table('invitation_categories')
            ->where('slug', 'anggota-senat')
            ->update(['slug' => 'anggotasenat']);
    }

    public function down(): void
    {
        DB::table('invitation_categories')
            ->where('slug', 'ketuasenat')
            ->update(['slug' => 'ketua-senat']);

        DB::table('invitation_categories')
            ->where('slug', 'anggotasenat')
            ->update(['slug' => 'anggota-senat']);
    }
};
