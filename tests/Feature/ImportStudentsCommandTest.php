<?php

namespace Tests\Feature;

use App\Models\StudyProgram;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportStudentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_students_replaces_existing_roster_from_absensi_workbook(): void
    {
        $path = database_path('imports/absensi-mahasiswa-september-2026.xlsx');
        if (! is_file($path)) {
            $this->markTestSkipped('File absensi deployment belum tersedia.');
        }

        $period = YudisiumPeriod::query()->create([
            'name' => 'Yudisium Tahun 2026 Angkatan 83 Periode 3',
            'slug' => 'yudisium-tahun-2026-angkatan-83-periode-3',
            'event_year' => 2026,
            'event_date' => '2026-09-17',
            'location' => 'Gedung Hexagon',
            'is_active' => true,
            'is_published' => true,
        ]);

        $program = StudyProgram::query()->create([
            'code' => '09',
            'name' => 'Informatika',
            'sort_order' => 9,
            'is_active' => true,
        ]);

        YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'study_program_id' => $program->id,
            'sequence_number' => 99,
            'nim' => '0000000001',
            'name' => 'Mahasiswa Lama Harus Hilang',
            'study_program' => 'Informatika',
        ]);

        $this->artisan('yudisium:import-students', [
            'file' => $path,
            '--period' => $period->slug,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('yudisium_participants', [
            'nim' => '0000000001',
        ]);
        $this->assertDatabaseHas('yudisium_participants', [
            'nim' => '2109056004',
            'name' => 'Intan Wulandari',
            'period_id' => $period->id,
        ]);
        $this->assertGreaterThanOrEqual(250, YudisiumParticipant::query()->where('period_id', $period->id)->count());
    }
}
