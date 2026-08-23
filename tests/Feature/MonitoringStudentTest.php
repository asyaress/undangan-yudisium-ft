<?php

namespace Tests\Feature;

use App\Models\StudyProgram;
use App\Models\User;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_monitoring_is_grouped_by_study_program(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $period = YudisiumPeriod::query()->create([
            'name' => 'Yudisium Angkatan 83 Periode 3',
            'slug' => 'yudisium-angkatan-83-periode-3',
            'event_year' => 2026,
            'event_date' => '2026-09-12',
            'location' => 'Gedung Hexagon',
            'is_active' => true,
            'is_published' => true,
        ]);
        $sipil = StudyProgram::query()->create([
            'code' => '22201',
            'name' => 'Teknik Sipil',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $informatika = StudyProgram::query()->create([
            'code' => '55201',
            'name' => 'Informatika',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'sequence_number' => 1,
            'study_program_id' => $informatika->id,
            'nim' => '2200000002',
            'name' => 'Mahasiswa Informatika',
            'study_program' => $informatika->name,
            'rsvp_status' => 'attending',
            'rsvp_responded_at' => now(),
        ]);
        YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'sequence_number' => 9,
            'study_program_id' => $sipil->id,
            'nim' => '2200000001',
            'name' => 'Mahasiswa Sipil',
            'study_program' => $sipil->name,
        ]);

        $this->actingAs($admin)
            ->get(route('monitoring.mahasiswa', ['period_id' => $period->id]))
            ->assertOk()
            ->assertSee('Program Studi')
            ->assertSee('Semua program studi')
            ->assertSee('Teknik Sipil')
            ->assertSee('Informatika')
            ->assertDontSee('Live tanpa refresh')
            ->assertDontSee('Aktifkan bunyi');

        $this->actingAs($admin)
            ->getJson(route('monitoring.live', ['type' => 'mahasiswa', 'period_id' => $period->id]))
            ->assertOk()
            ->assertJsonPath('rows.0.study_program_name', 'Teknik Sipil')
            ->assertJsonPath('rows.1.study_program_name', 'Informatika')
            ->assertJsonPath('rows.0.study_program_key', 'program-'.$sipil->id);
    }
}
