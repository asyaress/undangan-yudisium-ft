<?php

namespace Tests\Feature;

use App\Models\StudyProgram;
use App\Models\User;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParticipantManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_student_manually_with_auto_sequence(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $period = $this->period();
        $studyProgram = $this->studyProgram();

        YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'sequence_number' => 7,
            'study_program_id' => $studyProgram->id,
            'nim' => '2100000001',
            'name' => 'Mahasiswa Lama',
            'study_program' => $studyProgram->name,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.participants.store'), [
                'period_id' => $period->id,
                'study_program_id' => $studyProgram->id,
                'nim' => '2100000002',
                'name' => 'Mahasiswa Manual',
                'birth_date' => '2001-04-12',
                'email' => 'manual@example.com',
                'phone' => '08123456789',
            ])
            ->assertRedirect(route('admin.participants.index', ['period_id' => $period->id]))
            ->assertSessionHas('success', 'Data mahasiswa berhasil ditambahkan.');

        $this->assertDatabaseHas('yudisium_participants', [
            'period_id' => $period->id,
            'sequence_number' => 8,
            'study_program_id' => $studyProgram->id,
            'nim' => '2100000002',
            'name' => 'Mahasiswa Manual',
            'study_program' => 'Teknik Sipil',
            'faculty' => 'Fakultas Teknik',
            'email' => 'manual@example.com',
            'phone' => '08123456789',
        ]);
    }

    public function test_manual_student_nim_must_be_numeric(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $period = $this->period();
        $studyProgram = $this->studyProgram();

        $this->actingAs($admin)
            ->post(route('admin.participants.store'), [
                'period_id' => $period->id,
                'study_program_id' => $studyProgram->id,
                'nim' => 'ABC123',
                'name' => 'Mahasiswa Manual',
            ])
            ->assertSessionHasErrors(['nim']);
    }

    private function period(): YudisiumPeriod
    {
        return YudisiumPeriod::query()->create([
            'name' => 'Yudisium Test',
            'slug' => 'yudisium-test',
            'event_year' => 2026,
            'event_date' => '2026-09-12',
            'location' => 'Gedung Hexagon',
            'is_active' => true,
            'is_published' => true,
        ]);
    }

    private function studyProgram(): StudyProgram
    {
        return StudyProgram::query()->create([
            'code' => '01',
            'name' => 'Teknik Sipil',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }
}
