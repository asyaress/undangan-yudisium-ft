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
        $this->assertSame($pejabat->id, $second->fresh()->invitationCategory()?->id);

        $directory->syncRoles($second->fresh(['roles']), [
            ['category_id' => $pejabat->id, 'position' => 'Koordinator Program Doktor'],
            ['category_id' => $senat->id, 'position' => 'Ketua Komisi Etik Fakultas'],
        ], 1);

        $recipient = $second->fresh(['roles', 'category', 'period']);
        $this->assertSame('Koordinator Program Doktor', $recipient->displayPosition());
        $this->assertSame($pejabat->id, $recipient->invitationCategory()?->id);
        $this->assertSame(['Koordinator Program Doktor', 'Ketua Komisi Etik Fakultas'], $recipient->listedPositions());

        $this->get(route('home', [
            'event' => $period->slug,
            'to' => $senat->slug,
            'ref' => $recipient->token,
        ]))->assertRedirect($recipient->invitationUrl());

        $this->get($recipient->invitationUrl())
            ->assertOk()
            ->assertSee('Prof. Dr. Ir. Anindita Septiarini, S.T., M.Cs.')
            ->assertSee('Ketua Komisi Etik Fakultas')
            ->assertSee('Koordinator Program Doktor');

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

    public function test_tendik_with_kepala_bagian_uses_private_invitation(): void
    {
        $period = $this->period();
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $tendik = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true, 8);
        $directory = app(RecipientDirectory::class);

        $staff = $directory->upsert($tendik, [
            'name' => 'Rajab Abdul',
            'identifier' => '198001012006041001',
            'position' => 'Kepala Bagian Tata Usaha',
            'salutation' => 'Bapak',
        ]);

        $staff = $directory->upsert($tendik, [
            'name' => 'Rajab Abdul',
            'identifier' => '198001012006041001',
            'position' => 'Tenaga Kependidikan',
            'salutation' => 'Bapak',
        ]);

        $this->assertSame($pejabat->id, $staff->invitationCategory()?->id);
        $this->assertSame('Kepala Bagian Tata Usaha', $staff->displayPosition());
        $this->assertContains('Tenaga Kependidikan', $staff->listedPositions());
        $this->assertStringContainsString('to=pejabat', $staff->invitationUrl());
        $this->assertStringContainsString('ref=', $staff->invitationUrl());

        $this->post(route('undangan.verify-recipient'), [
            'event_id' => $period->id,
            'category_slug' => $tendik->slug,
            'lookup_value' => '198001012006041001',
        ])->assertRedirect($staff->fresh()->invitationUrl());

        $this->get(route('home', [
            'event' => $period->slug,
            'to' => $tendik->slug,
            'ref' => $staff->token,
        ]))->assertRedirect($staff->fresh()->invitationUrl());
    }

    public function test_regular_tendik_keeps_nip_invitation(): void
    {
        $period = $this->period();
        $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $tendik = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true, 8);
        $directory = app(RecipientDirectory::class);

        $staff = $directory->upsert($tendik, [
            'name' => 'Staf Administrasi',
            'identifier' => '198001012006041002',
            'position' => 'Tenaga Kependidikan',
            'salutation' => 'Bapak',
        ]);

        $this->assertSame($tendik->id, $staff->invitationCategory()?->id);
        $this->assertStringContainsString('to=tendik', $staff->invitationUrl());
        $this->assertStringContainsString('ref=', $staff->invitationUrl());
    }

    public function test_tendik_ketua_sub_pokja_uses_private_invitation(): void
    {
        $period = $this->period();
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $tendik = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true, 8);
        $directory = app(RecipientDirectory::class);

        $staff = $directory->upsert($tendik, [
            'name' => 'Abdul Rajab Dei, SE., MM.',
            'identifier' => '198304302009101003',
            'position' => 'Ketua Sub Kelompok Kerja Akademik',
            'salutation' => 'Bapak',
        ]);

        $staff = $directory->upsert($pejabat, [
            'name' => 'Abdul Rajab Dei, SE., MM.',
            'identifier' => '198304302009101003',
            'position' => 'Ketua Sub Pokja Akademik',
            'salutation' => 'Bapak',
        ]);

        $this->assertSame(1, InvitationRecipient::query()->count());
        $this->assertSame($pejabat->id, $staff->invitationCategory()?->id);
        $this->assertSame('Ketua Sub Pokja Akademik', $staff->displayPosition());
        $this->assertSame(['Ketua Sub Pokja Akademik'], $staff->listedPositions());
        $this->assertStringContainsString('to=pejabat', $staff->invitationUrl());
        $this->assertStringContainsString('ref=', $staff->invitationUrl());

        $this->post(route('undangan.verify-recipient'), [
            'event_id' => $period->id,
            'category_slug' => $tendik->slug,
            'lookup_value' => '198304302009101003',
        ])->assertRedirect($staff->fresh()->invitationUrl());
    }

    public function test_sub_pokja_and_sub_kelompok_kerja_same_scope_use_pokja_title(): void
    {
        $period = $this->period();
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $directory = app(RecipientDirectory::class);

        $recipient = $directory->upsert($pejabat, [
            'name' => 'Budi Keuangan',
            'identifier' => '198001012010011002',
            'position' => 'Ketua Sub Kelompok Kerja Keuangan dan Umum',
        ]);

        $directory->upsert($pejabat, [
            'name' => 'Budi Keuangan',
            'identifier' => '198001012010011002',
            'position' => 'Ketua Sub Pokja Keuangan dan Umum',
        ]);

        $recipient = $recipient->fresh(['roles', 'category', 'period']);

        $this->assertSame(['Ketua Sub Pokja Keuangan dan Umum'], $recipient->listedPositions());
    }

    public function test_kps_shorthand_and_koordinator_prodi_are_one_listed_position(): void
    {
        $period = $this->period();
        $kps = $this->category($period, 'kps', InvitationCategory::ACCESS_PRIVATE, true, 4);
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true, 3);
        $directory = app(RecipientDirectory::class);

        $recipient = $directory->upsert($kps, [
            'name' => 'Dr. Informatika, M.Kom.',
            'identifier' => '198001012010011001',
            'position' => 'KPS S2 Informatika',
        ]);

        $directory->upsert($pejabat, [
            'name' => 'Dr. Informatika, M.Kom.',
            'identifier' => '198001012010011001',
            'position' => 'Koordinator Program Studi Magister Informatika',
        ]);

        $recipient = $recipient->fresh(['roles', 'category', 'period']);

        $this->assertSame(
            ['Koordinator Program Studi Magister Informatika'],
            $recipient->listedPositions(),
        );

        $this->get($recipient->invitationUrl())
            ->assertOk()
            ->assertSee('Koordinator Program Studi Magister Informatika')
            ->assertDontSee('KPS S2 Informatika');
    }

    public function test_plt_s1_coordinator_is_the_same_kps_seat(): void
    {
        $period = $this->period();
        $kps = $this->category($period, 'kps', InvitationCategory::ACCESS_PRIVATE, true, 4);
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true, 3);
        $directory = app(RecipientDirectory::class);

        $recipient = $directory->upsert($kps, [
            'name' => 'Albertus Juvensius Pontus, MT.',
            'identifier' => '198001012010011088',
            'position' => 'Plt. Koordinator Program Studi S1 Teknik Pertambangan',
        ]);

        $directory->upsert($pejabat, [
            'name' => 'Albertus Juvensius Pontus, MT.',
            'identifier' => '198001012010011088',
            'position' => 'Koordinator Program Studi Teknik Pertambangan',
        ]);

        $recipient = $recipient->fresh(['roles', 'category', 'period']);

        $this->assertSame(1, InvitationRecipient::query()->count());
        $this->assertSame(2, $recipient->roles()->count());
        $this->assertSame(
            ['Plt. Koordinator Program Studi S1 Teknik Pertambangan'],
            $recipient->listedPositions(),
        );
        $this->assertSame(
            ['Kategori kps'],
            $directory->displayCategories($recipient->roles),
        );
    }

    public function test_kalab_and_pejabat_lab_head_are_one_display_category(): void
    {
        $period = $this->period();
        $kalab = $this->category($period, 'kalab', InvitationCategory::ACCESS_PRIVATE, true, 5);
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true, 3);
        $directory = app(RecipientDirectory::class);

        $recipient = $directory->upsert($kalab, [
            'name' => 'Dr. Laboratorium, M.T.',
            'identifier' => '198001012010011077',
            'position' => 'Kalab Komputasi',
        ]);

        $directory->upsert($pejabat, [
            'name' => 'Dr. Laboratorium, M.T.',
            'identifier' => '198001012010011077',
            'position' => 'Kepala Laboratorium Komputasi',
        ]);

        $recipient = $recipient->fresh(['roles', 'category', 'period']);

        $this->assertSame(1, InvitationRecipient::query()->count());
        $this->assertSame(2, $recipient->roles()->count());
        $this->assertSame(['Kepala Laboratorium Komputasi'], $recipient->listedPositions());
        $this->assertSame(['Kategori kalab'], $directory->displayCategories($recipient->roles));
    }

    public function test_tendik_kepala_bagian_does_not_duplicate_pejabat_category(): void
    {
        $period = $this->period();
        $pejabat = $this->category($period, 'pejabat', InvitationCategory::ACCESS_PRIVATE, true);
        $tendik = $this->category($period, 'tendik', InvitationCategory::ACCESS_NIP, true, 8);
        $directory = app(RecipientDirectory::class);

        $staff = $directory->upsert($tendik, [
            'name' => 'Rajab Abdul',
            'identifier' => '198001012006041001',
            'position' => 'Kepala Bagian Tata Usaha',
            'salutation' => 'Bapak',
        ]);

        $directory->upsert($pejabat, [
            'name' => 'Rajab Abdul',
            'identifier' => '198001012006041001',
            'position' => 'Kepala Bagian Tata Usaha',
            'salutation' => 'Bapak',
        ]);

        $staff = $staff->fresh(['roles', 'category', 'period']);

        $this->assertSame(1, InvitationRecipient::query()->count());
        $this->assertSame(['Kepala Bagian Tata Usaha'], $staff->listedPositions());
        $this->assertSame(['Kategori pejabat'], $directory->displayCategories($staff->roles));
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

    private function category(YudisiumPeriod $period, string $slug, string $accessMode, bool $rsvpEnabled = false, int $sortOrder = 1): InvitationCategory
    {
        return InvitationCategory::query()->create([
            'period_id' => $period->id,
            'slug' => $slug,
            'title' => 'Kategori '.$slug,
            'recipient_label' => 'Tamu Undangan',
            'cover_text' => 'Program Sarjana',
            'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu.',
            'closing_text' => 'Terima kasih.',
            'sort_order' => $sortOrder,
            'access_mode' => $accessMode,
            'rsvp_enabled' => $rsvpEnabled,
        ]);
    }

    private function signatureData(): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('signature-data', 12));
    }
}
