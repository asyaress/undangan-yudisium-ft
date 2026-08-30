<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_link_without_category_returns_not_found(): void
    {
        $period = $this->period();
        $this->category($period, 'umum', InvitationCategory::ACCESS_PUBLIC);

        $this->get('/?event='.$period->slug)->assertNotFound();
    }

    public function test_invalid_category_returns_not_found(): void
    {
        $period = $this->period();
        $this->category($period, 'umum', InvitationCategory::ACCESS_PUBLIC);

        $this->get('/?event='.$period->slug.'&to=tidak-ada')->assertNotFound();
    }

    public function test_private_category_without_token_returns_not_found(): void
    {
        $period = $this->period();
        $this->category($period, 'private', InvitationCategory::ACCESS_PRIVATE);

        $this->get('/?event='.$period->slug.'&to=private')->assertNotFound();
    }

    public function test_public_category_link_still_opens_invitation(): void
    {
        $period = $this->period();
        $this->category($period, 'umum', InvitationCategory::ACCESS_PUBLIC);

        $this->get('/?event='.$period->slug.'&to=umum')
            ->assertOk()
            ->assertSee('formalPreviewStage')
            ->assertSee('Dengan hormat')
            ->assertDontSee('Unduh PNG');
    }

    public function test_student_category_uses_generic_link_and_nim_gate(): void
    {
        $period = $this->period();
        $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);

        $this->get('/?event='.$period->slug.'&to=yudisiawan')
            ->assertOk()
            ->assertSee('NIM Mahasiswa')
            ->assertSee('Buka Undangan')
            ->assertDontSee('formalPreviewStage');
    }

    public function test_valid_nim_opens_student_invitation_without_birth_date(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);
        $participant = $this->participant($period);

        $this->post(route('undangan.verify-nim'), [
            'event_id' => $period->id,
            'category_slug' => $category->slug,
            'nim' => $participant->nim,
        ])
            ->assertRedirect(route('home', [
                'event' => $period->slug,
                'to' => $category->slug,
                'ref' => $participant->invitation_token,
            ]));
    }

    public function test_invalid_nim_returns_to_generic_student_link(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);

        $this->post(route('undangan.verify-nim'), [
            'event_id' => $period->id,
            'category_slug' => $category->slug,
            'nim' => '9999999999',
        ])
            ->assertRedirect(route('home', ['event' => $period->slug, 'to' => $category->slug]).'#rsvpSection')
            ->assertSessionHas('error', 'NIM tidak ditemukan. Periksa kembali angka NIM sesuai KTM/KRS. Jika masih gagal, hubungi panitia.');
    }

    public function test_nim_with_letters_is_rejected(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);

        $this->post(route('undangan.verify-nim'), [
            'event_id' => $period->id,
            'category_slug' => $category->slug,
            'nim' => 'ABC123',
        ])
            ->assertSessionHasErrors([
                'nim' => 'Masukkan NIM dalam format angka.',
            ]);
    }

    public function test_student_token_link_opens_invitation_after_nim_verification(): void
    {
        $period = $this->period();
        $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);
        $participant = $this->participant($period);

        $this->get('/?event='.$period->slug.'&to=yudisiawan&ref='.$participant->invitation_token)
            ->assertOk()
            ->assertSee('formalPreviewStage')
            ->assertSee($participant->name)
            ->assertDontSee('Unduh PNG')
            ->assertDontSee('QR Buku Tamu')
            ->assertDontSee('Kartu Registrasi Mahasiswa');
    }

    public function test_student_confirmation_success_shows_qr_download_guide(): void
    {
        $period = $this->period();
        $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);
        $participant = $this->participant($period);
        $participant->submitRsvp('attending');

        $this->withSession(['success' => 'Konfirmasi hadir berhasil disimpan.'])
            ->get('/?event='.$period->slug.'&to=yudisiawan&ref='.$participant->invitation_token.'#letterRsvp')
            ->assertOk()
            ->assertSee('Unduh kartu konfirmasi ini', false)
            ->assertSee('tunjukkan QR-nya di meja registrasi', false);
    }

    public function test_student_qr_card_is_hidden_when_confirmation_is_declined(): void
    {
        $period = $this->period();
        $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);
        $participant = $this->participant($period);
        $participant->submitRsvp('declined', 'Berhalangan hadir.');

        $this->withSession(['success' => 'Konfirmasi berhalangan berhasil disimpan.'])
            ->get('/?event='.$period->slug.'&to=yudisiawan&ref='.$participant->invitation_token.'#letterRsvp')
            ->assertOk()
            ->assertSee('Berhalangan Hadir')
            ->assertDontSee('Unduh PNG')
            ->assertDontSee('Unduh kartu konfirmasi ini', false);
    }

    public function test_private_recipient_attending_requires_signature(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $recipient = $this->recipient($period, $category);

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'attending',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Mohon isi tanda tangan terlebih dahulu.');

        $this->assertSame('pending', $recipient->fresh()->rsvp_status);
    }

    public function test_private_recipient_attending_saves_signature(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $recipient = $this->recipient($period, $category);
        $signature = $this->signatureData();

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'attending',
            'rsvp_signature' => $signature,
            'signature_drawn' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Konfirmasi hadir berhasil disimpan.');

        $recipient->refresh();
        $this->assertSame('attending', $recipient->rsvp_status);
        $this->assertSame($signature, $recipient->rsvp_signature);
    }

    public function test_private_recipient_represented_requires_representative_paraf(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $recipient = $this->recipient($period, $category);

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'represented',
            'representative_name' => 'Perwakilan Test',
            'representative_position' => 'Sekretaris',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Mohon isi paraf perwakilan terlebih dahulu.');

        $this->assertSame('pending', $recipient->fresh()->rsvp_status);
    }

    public function test_private_recipient_represented_saves_representative_paraf(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $recipient = $this->recipient($period, $category);
        $signature = $this->signatureData();

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'represented',
            'representative_name' => 'Perwakilan Test',
            'representative_position' => 'Sekretaris',
            'rsvp_signature' => $signature,
            'signature_drawn' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Konfirmasi diwakilkan berhasil disimpan.');

        $recipient->refresh();
        $this->assertSame('represented', $recipient->rsvp_status);
        $this->assertSame("Diwakilkan oleh: Perwakilan Test\nJabatan: Sekretaris", $recipient->rsvp_note);
        $this->assertSame($signature, $recipient->rsvp_signature);
    }

    public function test_private_recipient_declined_clears_signature(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $recipient = $this->recipient($period, $category);
        $recipient->forceFill(['rsvp_signature' => $this->signatureData()])->save();

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'declined',
            'note' => 'Ada agenda lain.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Konfirmasi berhalangan hadir berhasil disimpan.');

        $recipient->refresh();
        $this->assertSame('declined', $recipient->rsvp_status);
        $this->assertNull($recipient->rsvp_signature);
    }

    public function test_nip_lookup_opens_recipient_invitation(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true);
        $recipient = $this->recipient($period, $category, ['identifier' => '198001012010011001', 'position' => 'Tenaga Kependidikan']);

        $this->post(route('undangan.verify-recipient'), [
            'event_id' => $period->id,
            'category_slug' => $category->slug,
            'lookup_value' => $recipient->identifier,
        ])
            ->assertRedirect(route('home', [
                'event' => $period->slug,
                'to' => $category->slug,
                'ref' => $recipient->token,
            ]));
    }

    public function test_nip_category_without_ref_shows_lookup_gate(): void
    {
        $period = $this->period();
        $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true);

        $this->get('/?event='.$period->slug.'&to=tendik')
            ->assertOk()
            ->assertSee('NIP Penerima')
            ->assertDontSee('formalPreviewStage')
            ->assertDontSee('Link undangan belum valid');
    }

    public function test_nip_lookup_rejects_non_numeric_value(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true);

        $this->post(route('undangan.verify-recipient'), [
            'event_id' => $period->id,
            'category_slug' => $category->slug,
            'lookup_value' => 'NIP-123',
        ])
            ->assertRedirect(route('home', ['event' => $period->slug, 'to' => $category->slug]).'#rsvpSection')
            ->assertSessionHas('error', 'Masukkan NIP dengan angka.');
    }

    public function test_name_lookup_opens_recipient_invitation(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'satpam', InvitationCategory::ACCESS_NAME, true);
        $recipient = $this->recipient($period, $category, ['name' => 'Satpam Test', 'position' => 'Satpam']);

        $this->post(route('undangan.verify-recipient'), [
            'event_id' => $period->id,
            'category_slug' => $category->slug,
            'lookup_value' => 'satpam test',
        ])
            ->assertRedirect(route('home', [
                'event' => $period->slug,
                'to' => $category->slug,
                'ref' => $recipient->token,
            ]));
    }

    public function test_nip_recipient_attending_requires_signature(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true);
        $recipient = $this->recipient($period, $category, ['identifier' => '198001012010011001']);

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'attending',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Mohon isi tanda tangan terlebih dahulu.');
    }

    public function test_name_lookup_recipient_attending_does_not_require_signature(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'cs', InvitationCategory::ACCESS_NAME, true);
        $recipient = $this->recipient($period, $category, ['name' => 'Cleaning Service Test', 'position' => 'Cleaning Service']);

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'attending',
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Konfirmasi hadir berhasil disimpan.');

        $recipient->refresh();
        $this->assertSame('attending', $recipient->rsvp_status);
        $this->assertNull($recipient->rsvp_signature);
    }

    public function test_lookup_recipient_cannot_submit_represented_status(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'satpam', InvitationCategory::ACCESS_NAME, true);
        $recipient = $this->recipient($period, $category, ['name' => 'Satpam Test']);

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'represented',
            'representative_name' => 'Perwakilan',
            'representative_position' => 'Koordinator',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Konfirmasi diwakilkan tidak tersedia untuk kategori undangan ini.');

        $this->assertSame('pending', $recipient->fresh()->rsvp_status);
    }

    private function period(): YudisiumPeriod
    {
        return YudisiumPeriod::query()->create([
            'name' => 'Yudisium Test',
            'slug' => 'yudisium-test',
            'event_year' => 2026,
            'event_date' => '2026-06-18',
            'location' => 'Gedung Fakultas Teknik',
            'is_active' => true,
            'is_published' => true,
        ]);
    }

    private function category(YudisiumPeriod $period, string $slug, string $accessMode, bool $rsvpEnabled = false): InvitationCategory
    {
        return InvitationCategory::query()->create([
            'period_id' => $period->id,
            'slug' => $slug,
            'title' => 'Kategori Test',
            'recipient_label' => 'Tamu Undangan',
            'cover_text' => 'Program Sarjana',
            'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu.',
            'closing_text' => 'Terima kasih.',
            'sort_order' => 1,
            'access_mode' => $accessMode,
            'rsvp_enabled' => $rsvpEnabled,
        ]);
    }

    private function participant(YudisiumPeriod $period): YudisiumParticipant
    {
        return YudisiumParticipant::query()->create([
            'period_id' => $period->id,
            'nim' => '2200000001',
            'name' => 'Mahasiswa Test',
            'study_program' => 'Teknik Informatika',
            'faculty' => 'Fakultas Teknik',
        ]);
    }

    private function recipient(YudisiumPeriod $period, InvitationCategory $category, array $attributes = []): InvitationRecipient
    {
        return InvitationRecipient::query()->create([
            'period_id' => $period->id,
            'category_id' => $category->id,
            'salutation' => 'Bapak',
            'name' => 'Pejabat Test',
            ...$attributes,
        ]);
    }

    private function signatureData(): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('signature-data', 12));
    }
}
