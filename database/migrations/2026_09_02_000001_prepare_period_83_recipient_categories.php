<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $periodId = $this->periodId();

        if (! $periodId) {
            return;
        }

        foreach ($this->categories() as $category) {
            $this->saveCategory($periodId, $category['slug'], $category);
        }

        $this->mergeLegacyCategory($periodId, 'satpam', 'tenaga-keamanan');
        $this->mergeLegacyCategory($periodId, 'cs', 'tenaga-cs');
    }

    public function down(): void
    {
        //
    }

    private function periodId(): ?int
    {
        return DB::table('yudisium_periods')
            ->where('slug', 'yudisium-tahun-2026-angkatan-83-periode-3')
            ->value('id')
            ?: DB::table('yudisium_periods')
                ->where('name', 'like', '%Angkatan 83%')
                ->where('name', 'like', '%Periode 3%')
                ->value('id');
    }

    private function saveCategory(int $periodId, string $slug, array $category): void
    {
        $now = now();
        unset($category['slug']);

        $existing = DB::table('invitation_categories')
            ->where('period_id', $periodId)
            ->where('slug', $slug)
            ->first();

        if ($existing) {
            DB::table('invitation_categories')
                ->where('id', $existing->id)
                ->update([
                    ...$category,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('invitation_categories')->insert([
            'period_id' => $periodId,
            'slug' => $slug,
            ...$category,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function mergeLegacyCategory(int $periodId, string $legacySlug, string $targetSlug): void
    {
        $legacyId = DB::table('invitation_categories')
            ->where('period_id', $periodId)
            ->where('slug', $legacySlug)
            ->value('id');
        $targetId = DB::table('invitation_categories')
            ->where('period_id', $periodId)
            ->where('slug', $targetSlug)
            ->value('id');

        if (! $legacyId || ! $targetId) {
            return;
        }

        DB::table('invitation_recipients')
            ->where('period_id', $periodId)
            ->where('category_id', $legacyId)
            ->update(['category_id' => $targetId]);

        DB::table('invitation_categories')
            ->where('id', $legacyId)
            ->delete();
    }

    private function categories(): array
    {
        return [
            [
                'slug' => 'pejabat',
                'title' => 'Pejabat Fakultas dan Universitas',
                'recipient_label' => 'Pejabat Fakultas dan Universitas',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang pejabat Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 3,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'kps',
                'title' => 'Koordinator Program Studi',
                'recipient_label' => 'Koordinator Program Studi Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Koordinator Program Studi dan UJM Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 4,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'kalab',
                'title' => 'Kepala Laboratorium',
                'recipient_label' => 'Kepala Laboratorium Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Kepala Laboratorium Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 5,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'ketuasenat',
                'title' => 'Ketua Senat Fakultas Teknik',
                'recipient_label' => 'Ketua Senat Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Ketua Senat Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 6,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'anggota-senat-fakultas-teknik',
                'title' => 'Anggota Senat Fakultas Teknik',
                'recipient_label' => 'Anggota Senat Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Anggota Senat Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 7,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'tendik',
                'title' => 'Tenaga Kependidikan',
                'recipient_label' => 'Tenaga Kependidikan Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Tenaga Kependidikan Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 8,
                'access_mode' => 'nip',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'tenaga-cs',
                'title' => 'Tenaga Cleaning Service',
                'recipient_label' => 'Tenaga Cleaning Service Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Tenaga Cleaning Service Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 9,
                'access_mode' => 'name',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'tenaga-keamanan',
                'title' => 'Tenaga Keamanan',
                'recipient_label' => 'Tenaga Keamanan Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'invitation_text' => 'Dengan hormat, kami mengundang Tenaga Keamanan Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                'sort_order' => 10,
                'access_mode' => 'name',
                'rsvp_enabled' => true,
            ],
        ];
    }
};
