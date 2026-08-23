<?php

namespace Database\Seeders;

use App\Models\InvitationCategory;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\YudisiumPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        if ((! $adminEmail || ! $adminPassword) && app()->environment('production')) {
            throw new RuntimeException('ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi saat deploy production.');
        }

        if ($adminEmail && $adminPassword) {
            User::query()->updateOrCreate([
                'email' => $adminEmail,
            ], [
                'name' => env('ADMIN_NAME', 'Admin Monitoring'),
                'password' => $adminPassword,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        }

        $categories = [
            [
                'slug' => 'yudisiawan',
                'title' => 'Yudisiawan / Yudisiawati',
                'recipient_label' => 'Yudisiawan / Yudisiawati',
                'cover_text' => 'Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang kehadiran Bapak/Ibu/Saudara(i) pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang para Yudisiawan/Yudisiawati untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'sort_order' => 1,
                'access_mode' => 'nim',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'orangtua',
                'title' => 'Orangtua Yudisiawan',
                'recipient_label' => 'Orang Tua/Wali Yudisiawan / Yudisiawati',
                'cover_text' => 'Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang kehadiran Bapak/Ibu/Wali pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu Orang Tua/Wali Yudisiawan/Yudisiawati untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'sort_order' => 2,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
            [
                'slug' => 'pejabat',
                'title' => 'Pejabat Fakultas dan Universitas',
                'recipient_label' => 'Pejabat Fakultas dan Universitas',
                'cover_text' => 'Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang para pejabat fakultas dan universitas pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu Pejabat Fakultas Teknik Universitas Mulawarman untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'sort_order' => 3,
                'access_mode' => 'public',
                'rsvp_enabled' => false,
            ],
            [
                'slug' => 'ketuasenat',
                'title' => 'Ketua Senat Fakultas Teknik',
                'recipient_label' => 'Ketua Senat Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang Ketua Senat Fakultas Teknik pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang Bapak Ketua Senat Fakultas Teknik untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'sort_order' => 4,
                'access_mode' => 'public',
                'rsvp_enabled' => false,
            ],
            [
                'slug' => 'anggotasenat',
                'title' => 'Seluruh Anggota Senat Fakultas Teknik',
                'recipient_label' => 'Seluruh Anggota Senat Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang seluruh anggota senat pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu Anggota Senat Fakultas Teknik untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'sort_order' => 5,
                'access_mode' => 'public',
                'rsvp_enabled' => false,
            ],
            [
                'slug' => 'tendik',
                'title' => 'Staf Tenaga Kependidikan',
                'recipient_label' => 'Staf Tenaga Kependidikan Fakultas Teknik',
                'cover_text' => 'Program Sarjana Angkatan 82 Periode 2 Tahun 2026. Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang staf tenaga kependidikan pada prosesi yudisium.',
                'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu Staf Tenaga Kependidikan Fakultas Teknik untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
                'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
                'sort_order' => 6,
                'access_mode' => 'private',
                'rsvp_enabled' => true,
            ],
        ];

        $periodId = YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');

        if ($periodId) {
            foreach ($categories as $category) {
                InvitationCategory::updateOrCreate(
                    ['period_id' => $periodId, 'slug' => $category['slug']],
                    ['period_id' => $periodId, ...$category]
                );
            }
        }

        $studyPrograms = [
            ['code' => '01', 'name' => 'Teknik Sipil'],
            ['code' => '02', 'name' => 'Teknik Industri'],
            ['code' => '03', 'name' => 'Teknik Pertambangan'],
            ['code' => '04', 'name' => 'Teknik Lingkungan'],
            ['code' => '05', 'name' => 'Teknik Kimia'],
            ['code' => '06', 'name' => 'Teknik Geologi'],
            ['code' => '07', 'name' => 'Teknik Elektro'],
            ['code' => '08', 'name' => 'Arsitektur'],
            ['code' => '09', 'name' => 'Informatika'],
            ['code' => '10', 'name' => 'Sistem Informasi'],
        ];

        foreach ($studyPrograms as $index => $studyProgram) {
            StudyProgram::updateOrCreate(
                ['code' => $studyProgram['code']],
                [
                    'name' => $studyProgram['name'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

    }
}
