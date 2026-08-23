<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
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
            ->assertSee('Dengan hormat');
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

    private function category(YudisiumPeriod $period, string $slug, string $accessMode): InvitationCategory
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
            'rsvp_enabled' => false,
        ]);
    }
}
