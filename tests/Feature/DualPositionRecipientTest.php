<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumPeriod;
use App\Services\RecipientDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualPositionRecipientTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_person_keeps_one_invitation_and_shared_rsvp(): void
    {
        $period = $this->period();
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $senat = $this->category($period, 'ketuasenat', InvitationCategory::ACCESS_PRIVATE, true);
        $directory = app(RecipientDirectory::class);

        $first = $directory->upsert($pejabat, [
            'name' => 'Prof. Dr. Ir. Anindita Septiarini, S.T., M.Cs.',
            'identifier' => '198001012010012001',
            'position' => 'Koordinator Program Doktor',
            'salutation' => 'Ibu',
        ]);

        $second = $directory->upsert($senat, [
            'name' => 'Prof. Dr. Ir. Anindita Septiarini, S.T., M.Cs.',
            'identifier' => '198001012010012001',
            'position' => 'Ketua Komisi Etik Fakultas',
            'salutation' => 'Ibu',
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, InvitationRecipient::query()->count());
        $this->assertSame(2, $second->roles()->count());
        $this->assertSame('Koordinator Program Doktor', $second->fresh()->displayPosition());

        $directory->syncRoles($second->fresh(['roles']), [
            ['category_id' => $pejabat->id, 'position' => 'Koordinator Program Doktor'],
            ['category_id' => $senat->id, 'position' => 'Ketua Komisi Etik Fakultas'],
        ], 1);

        $recipient = $second->fresh(['roles', 'category', 'period']);
        $this->assertSame('Ketua Komisi Etik Fakultas', $recipient->displayPosition());
        $this->assertSame($senat->id, $recipient->invitationCategory()?->id);

        $this->get(route('home', [
            'event' => $period->slug,
            'to' => $pejabat->slug,
            'ref' => $recipient->token,
        ]))->assertRedirect(route('home', [
            'event' => $period->slug,
            'to' => $senat->slug,
            'ref' => $recipient->token,
        ]));

        $this->get($recipient->invitationUrl())
            ->assertOk()
            ->assertSee('Prof. Dr. Ir. Anindita Septiarini, S.T., M.Cs.')
            ->assertSee('Ketua Komisi Etik Fakultas')
            ->assertDontSee('Koordinator Program Doktor');

        $this->post(route('rsvp.recipient'), [
            'recipient_id' => $recipient->id,
            'token' => $recipient->token,
            'attendance' => 'attending',
            'signature_drawn' => '1',
            'rsvp_signature' => $this->signatureData(),
        ])->assertRedirect();

        $recipient->refresh();
        $this->assertSame('attending', $recipient->rsvp_status);

        $this->assertTrue($directory->visibleInCategoryQuery($period->id, $pejabat->id)->whereKey($recipient->id)->exists());
        $this->assertTrue($directory->visibleInCategoryQuery($period->id, $senat->id)->whereKey($recipient->id)->exists());
        $this->assertSame('attending', $directory->visibleInCategoryQuery($period->id, $pejabat->id)->first()->rsvp_status);
        $this->assertSame('attending', $directory->visibleInCategoryQuery($period->id, $senat->id)->first()->rsvp_status);
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
            'title' => 'Kategori '.$slug,
            'recipient_label' => 'Tamu Undangan',
            'cover_text' => 'Program Sarjana',
            'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu.',
            'closing_text' => 'Terima kasih.',
            'sort_order' => 1,
            'access_mode' => $accessMode,
            'rsvp_enabled' => $rsvpEnabled,
        ]);
    }

    private function signatureData(): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('signature-data', 12));
    }
}
