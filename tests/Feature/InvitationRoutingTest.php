<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
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
            ->assertSee('Unduh PNG')
            ->assertDontSee('QR Buku Tamu')
            ->assertDontSee('Kartu Registrasi Mahasiswa');
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
}
