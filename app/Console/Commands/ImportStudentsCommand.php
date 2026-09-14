<?php

namespace App\Console\Commands;

use App\Models\StudyProgram;
use App\Models\YudisiumPeriod;
use App\Services\ExcelParticipantImporter;
use App\Services\ParticipantRosterMapper;
use App\Services\ParticipantRosterSync;
use Illuminate\Console\Command;

class ImportStudentsCommand extends Command
{
    protected $signature = 'yudisium:import-students
        {file? : Path file Excel absensi/mahasiswa}
        {--period=yudisium-tahun-2026-angkatan-83-periode-3 : Slug periode tujuan}
        {--keep-missing : Jangan hapus mahasiswa lama yang tidak ada di Excel}
        {--reset-rsvp : Reset RSVP dan check-in mahasiswa saat import}';

    protected $description = 'Ganti data mahasiswa dari Excel absensi untuk periode yudisium.';

    public function handle(
        ExcelParticipantImporter $importer,
        ParticipantRosterMapper $mapper,
        ParticipantRosterSync $sync
    ): int {
        $this->ensureStudyPrograms();

        $period = YudisiumPeriod::query()
            ->where('slug', (string) $this->option('period'))
            ->first();

        if (! $period) {
            $this->error('Periode tujuan tidak ditemukan: '.$this->option('period'));

            return self::FAILURE;
        }

        $path = $this->resolveWorkbookPath((string) $this->argument('file'));
        if (! $path) {
            $this->error('File Excel tidak ditemukan. Letakkan file di database/imports/absensi-mahasiswa-september-2026.xlsx atau kirim path sebagai argumen.');

            return self::FAILURE;
        }

        $this->line('File: '.$path);
        $this->line('Periode: '.$period->name);

        $rows = $importer->read($path);
        $records = $mapper->recordsFromRows($rows);

        if ($records === []) {
            $this->error('Data mahasiswa tidak ditemukan di Excel.');

            return self::FAILURE;
        }

        $summary = $sync->sync(
            $period,
            $records,
            replaceMissing: ! $this->option('keep-missing'),
            resetRsvp: (bool) $this->option('reset-rsvp'),
        );

        $this->info(sprintf(
            'Import mahasiswa selesai: %d tersimpan, %d gagal, %d dihapus.',
            $summary['saved'],
            $summary['failed'],
            $summary['deleted'],
        ));

        if ($summary['missing_birth_date'] > 0) {
            $this->warn($summary['missing_birth_date'].' data tanpa tanggal lahir. Undangan mahasiswa tetap dibuka dengan NIM.');
        }

        foreach (array_slice($summary['errors'], 0, 30) as $error) {
            $this->line(' - '.$error);
        }

        if ($summary['failed'] > 0 && $summary['saved'] === 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function ensureStudyPrograms(): void
    {
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
            StudyProgram::query()->updateOrCreate(
                ['code' => $studyProgram['code']],
                [
                    'name' => $studyProgram['name'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }

    private function resolveWorkbookPath(string $argument): ?string
    {
        $candidates = array_filter([
            $argument !== '' ? $argument : null,
            $argument !== '' ? base_path($argument) : null,
            database_path('imports/absensi-mahasiswa-september-2026.xlsx'),
            base_path('Absensi Mahasiswa  September 2026.xlsx'),
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
