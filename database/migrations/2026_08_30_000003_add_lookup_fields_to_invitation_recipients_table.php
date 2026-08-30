<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->string('identifier')->nullable()->after('context_note');
            $table->string('position')->nullable()->after('identifier');
        });

        DB::table('invitation_categories')
            ->where('slug', 'tendik')
            ->update(['access_mode' => 'nip', 'rsvp_enabled' => true]);

        $periodIds = DB::table('yudisium_periods')->pluck('id');

        foreach ($periodIds as $periodId) {
            $nextOrder = (int) DB::table('invitation_categories')
                ->where('period_id', $periodId)
                ->max('sort_order');

            foreach ($this->semiPrivateCategories() as $category) {
                $exists = DB::table('invitation_categories')
                    ->where('period_id', $periodId)
                    ->where('slug', $category['slug'])
                    ->exists();

                if ($exists) {
                    DB::table('invitation_categories')
                        ->where('period_id', $periodId)
                        ->where('slug', $category['slug'])
                        ->update([
                            'access_mode' => 'name',
                            'rsvp_enabled' => true,
                        ]);

                    continue;
                }

                $nextOrder++;
                DB::table('invitation_categories')->insert([
                    'period_id' => $periodId,
                    ...$category,
                    'sort_order' => $nextOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->dropColumn(['identifier', 'position']);
        });
    }

    private function semiPrivateCategories(): array
    {
        return [
            [
                'slug' => 'satpam',
                'title' => 'Tenaga Satpam',
                'recipient_label' => 'Tenaga Satpam Fakultas Teknik',
                'cover_text' => 'Program Sarjana Tahun 2026. Fakultas Teknik Universitas Mulawarman mengundang tenaga satpam pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang tenaga satpam Fakultas Teknik Universitas Mulawarman untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'access_mode' => 'name',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'cs',
                'title' => 'Tenaga Cleaning Service',
                'recipient_label' => 'Tenaga Cleaning Service Fakultas Teknik',
                'cover_text' => 'Program Sarjana Tahun 2026. Fakultas Teknik Universitas Mulawarman mengundang tenaga cleaning service pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang tenaga cleaning service Fakultas Teknik Universitas Mulawarman untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'access_mode' => 'name',
                'rsvp_enabled' => true,
            ],
        ];
    }
};
