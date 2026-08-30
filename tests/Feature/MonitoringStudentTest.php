<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
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

    public function test_private_monitoring_shows_signature_and_exports_it_to_excel(): void
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
        $category = InvitationCategory::query()->create([
            'period_id' => $period->id,
            'slug' => 'pejabat',
            'title' => 'Pejabat',
            'recipient_label' => 'Pejabat Fakultas dan Universitas',
            'cover_text' => 'Undangan',
            'invitation_text' => 'Dengan hormat',
            'sort_order' => 1,
            'access_mode' => InvitationCategory::ACCESS_PRIVATE,
            'rsvp_enabled' => true,
        ]);
        $signature = 'data:image/png;base64,'.base64_encode('fake-png');
        $recipient = InvitationRecipient::query()->create([
            'period_id' => $period->id,
            'category_id' => $category->id,
            'salutation' => 'Bapak',
            'name' => 'Agus Winarno',
            'context_note' => 'Ketua Senat Fakultas Teknik',
            'rsvp_status' => 'attending',
            'rsvp_signature' => $signature,
            'responded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('monitoring.private', ['period_id' => $period->id]))
            ->assertOk()
            ->assertSee('Tanda tangan')
            ->assertSee('monitoring\/private\/signature\/'.$recipient->id, false);

        $this->actingAs($admin)
            ->getJson(route('monitoring.live', ['type' => 'private', 'period_id' => $period->id]))
            ->assertOk()
            ->assertJsonPath('rows.0.has_signature', true)
            ->assertJsonPath('rows.0.signature_label', 'Tanda tangan')
            ->assertJsonPath('rows.0.signature_url', route('monitoring.private.signature', $recipient));

        $this->actingAs($admin)
            ->get(route('monitoring.private.signature', $recipient))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertContent('fake-png');

        $export = $this->actingAs($admin)
            ->get(route('monitoring.export', ['type' => 'private', 'period_id' => $period->id, 'format' => 'xls']))
            ->assertOk();

        $content = $export->streamedContent();
        $this->assertStringContainsString('tanda_tangan_paraf', $content);
        $this->assertStringContainsString('<img src="'.$signature, $content);
    }
}
