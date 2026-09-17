<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use App\Services\RecipientDirectory;
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

    public function test_private_monitoring_keeps_one_row_for_recipients_with_two_roles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $period = YudisiumPeriod::query()->create([
            'name' => 'Yudisium Angkatan 83 Periode 3',
            'slug' => 'yudisium-angkatan-83-periode-3-dual',
            'event_year' => 2026,
            'event_date' => '2026-09-12',
            'location' => 'Gedung Hexagon',
            'is_active' => true,
            'is_published' => true,
        ]);
        $kps = InvitationCategory::query()->create([
            'period_id' => $period->id,
            'slug' => 'kps',
            'title' => 'Koordinator Program Studi',
            'recipient_label' => 'Koordinator Program Studi',
            'cover_text' => 'Undangan',
            'invitation_text' => 'Dengan hormat',
            'sort_order' => 4,
            'access_mode' => InvitationCategory::ACCESS_PRIVATE,
            'rsvp_enabled' => true,
        ]);
        $pejabat = InvitationCategory::query()->create([
            'period_id' => $period->id,
            'slug' => 'pejabat',
            'title' => 'Pejabat Fakultas dan Universitas',
            'recipient_label' => 'Pejabat Fakultas dan Universitas',
            'cover_text' => 'Undangan',
            'invitation_text' => 'Dengan hormat',
            'sort_order' => 3,
            'access_mode' => InvitationCategory::ACCESS_PRIVATE,
            'rsvp_enabled' => true,
        ]);
        $directory = app(RecipientDirectory::class);
        $recipient = $directory->upsert($kps, [
            'name' => 'Awang Harsa Kridalaksana, S.Kom., M.Kom.',
            'identifier' => '198001012010011099',
            'position' => 'Koordinator Program Studi Informatika',
            'salutation' => 'Bapak',
        ]);
        $directory->upsert($pejabat, [
            'name' => 'Awang Harsa Kridalaksana, S.Kom., M.Kom.',
            'identifier' => '198001012010011099',
            'position' => 'Koordinator Program Studi Informatika',
            'salutation' => 'Bapak',
        ]);

        $signature = 'data:image/png;base64,'.base64_encode('fake-png-dual');
        $recipient->forceFill([
            'rsvp_status' => 'attending',
            'rsvp_signature' => $signature,
            'responded_at' => now(),
        ])->save();

        $this->assertSame(1, InvitationRecipient::query()->count());
        $this->assertSame(2, $recipient->fresh()->roles()->count());

        $live = $this->actingAs($admin)
            ->getJson(route('monitoring.live', ['type' => 'private', 'period_id' => $period->id]))
            ->assertOk()
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.attending', 1)
            ->assertJsonPath('rows.0.name', 'Bapak Awang Harsa Kridalaksana, S.Kom., M.Kom.')
            ->assertJsonPath('rows.0.rsvp_status', 'attending')
            ->assertJsonPath('rows.0.has_signature', true);

        $row = $live->json('rows.0');
        $this->assertSame(['Koordinator Program Studi'], $row['categories']);
        $this->assertSame(['Koordinator Program Studi Informatika'], $row['positions']);
        $this->assertEqualsCanonicalizing(['kps', 'pejabat'], $row['category_keys']);

        $this->actingAs($admin)
            ->getJson(route('monitoring.live', [
                'type' => 'private',
                'period_id' => $period->id,
                'category' => $kps->slug,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.recipient_id', $recipient->id);

        $this->actingAs($admin)
            ->getJson(route('monitoring.live', [
                'type' => 'private',
                'period_id' => $period->id,
                'category' => $pejabat->slug,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'rows');

        $pdf = $this->actingAs($admin)
            ->get(route('monitoring.export', ['type' => 'private', 'period_id' => $period->id, 'format' => 'pdf']))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($pdf, '>Bapak Awang Harsa Kridalaksana, S.Kom., M.Kom.<'));
        $this->assertStringContainsString('Koordinator Program Studi', $pdf);
        $this->assertStringNotContainsString('Pejabat Fakultas dan Universitas', $pdf);

        $excel = $this->actingAs($admin)
            ->get(route('monitoring.export', ['type' => 'private', 'period_id' => $period->id, 'format' => 'xls']))
            ->assertOk()
            ->streamedContent();

        $this->assertSame(1, substr_count($excel, 'Bapak Awang Harsa Kridalaksana, S.Kom., M.Kom.'));

        $recipient->refresh();
        $this->assertSame('attending', $recipient->rsvp_status);
        $this->assertSame($signature, $recipient->rsvp_signature);
        $this->assertSame(2, $recipient->roles()->count());
    }

    public function test_student_monitoring_shows_signature_and_exports_it_to_excel(): void
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
        $signature = 'data:image/png;base64,'.base64_encode('fake-png');
        $participant = YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'sequence_number' => 1,
            'nim' => '2200000003',
            'name' => 'Mahasiswa Tanda Tangan',
            'study_program' => 'Informatika',
            'rsvp_status' => 'attending',
            'rsvp_signature' => $signature,
            'rsvp_responded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('monitoring.mahasiswa', ['period_id' => $period->id]))
            ->assertOk()
            ->assertSee('Tanda tangan')
            ->assertSee('monitoring\/mahasiswa\/signature\/'.$participant->id, false);

        $this->actingAs($admin)
            ->getJson(route('monitoring.live', ['type' => 'mahasiswa', 'period_id' => $period->id]))
            ->assertOk()
            ->assertJsonPath('rows.0.has_signature', true)
            ->assertJsonPath('rows.0.signature_label', 'Tanda tangan')
            ->assertJsonPath('rows.0.signature_url', route('monitoring.mahasiswa.signature', $participant));

        $this->actingAs($admin)
            ->get(route('monitoring.mahasiswa.signature', $participant))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertContent('fake-png');

        $this->actingAs($admin)
            ->get(route('monitoring.mahasiswa.signature', $participant).'?download=1')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="ttd-'.$participant->nim.'.png"');

        $export = $this->actingAs($admin)
            ->get(route('monitoring.export', ['type' => 'mahasiswa', 'period_id' => $period->id, 'format' => 'xls']))
            ->assertOk();

        $content = $export->streamedContent();
        $this->assertStringContainsString('tanda_tangan', $content);
        $this->assertStringContainsString('<img src="'.$signature, $content);
    }

    public function test_student_monitoring_shows_jpeg_signatures_from_rsvp(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $period = YudisiumPeriod::query()->create([
            'name' => 'Yudisium Angkatan 83 Periode 3',
            'slug' => 'yudisium-angkatan-83-periode-3-jpeg',
            'event_year' => 2026,
            'event_date' => '2026-09-12',
            'location' => 'Gedung Hexagon',
            'is_active' => true,
            'is_published' => true,
        ]);
        $signature = 'data:image/jpeg;base64,'.base64_encode('fake-jpeg-bytes');
        $participant = YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'sequence_number' => 1,
            'nim' => '2200000004',
            'name' => 'Mahasiswa Tanda Tangan Jpeg',
            'study_program' => 'Teknik Sipil',
            'rsvp_status' => 'attending',
            'rsvp_signature' => $signature,
            'rsvp_responded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('monitoring.live', ['type' => 'mahasiswa', 'period_id' => $period->id]))
            ->assertOk()
            ->assertJsonPath('rows.0.has_signature', true)
            ->assertJsonPath('rows.0.signature_url', route('monitoring.mahasiswa.signature', $participant));

        $this->actingAs($admin)
            ->get(route('monitoring.mahasiswa.signature', $participant))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertContent('fake-jpeg-bytes');

        $this->actingAs($admin)
            ->get(route('monitoring.mahasiswa.signature', $participant).'?download=1')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="ttd-'.$participant->nim.'.jpg"');
    }
}
