<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
use App\Models\User;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminShellPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_load_for_authenticated_admin(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-app', false)
            ->assertSee('Universitas Mulawarman')
            ->assertSee('Fakultas Teknik');

        foreach ([
            route('admin.events.index'),
            route('admin.events.create'),
            route('admin.participants.index'),
            route('admin.study-programs.index'),
            route('admin.categories.index'),
            route('admin.checkin.manual.index'),
            route('admin.checkin.scanner.index'),
            route('monitoring.mahasiswa'),
            route('monitoring.private'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_edit_category_invitation_opening_text(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        $period = YudisiumPeriod::query()->create([
            'name' => 'Yudisium Test',
            'slug' => 'yudisium-test',
            'event_year' => 2026,
            'event_date' => '2026-06-18',
            'location' => 'Gedung Fakultas Teknik',
            'is_active' => true,
            'is_published' => true,
        ]);

        $category = InvitationCategory::query()->create([
            'period_id' => $period->id,
            'slug' => 'pejabat',
            'title' => 'Pejabat Fakultas dan Universitas',
            'recipient_label' => 'Pejabat Fakultas dan Universitas',
            'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
            'invitation_text' => 'Teks lama.',
            'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
            'sort_order' => 3,
            'access_mode' => InvitationCategory::ACCESS_PRIVATE,
            'rsvp_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('Teks Pembuka Surat')
            ->assertSee('Teks lama.');

        $updated = 'Dengan hormat, kami mengundang Pejabat Fakultas dan Universitas Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.';

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'period_id' => $period->id,
                'title' => $category->title,
                'recipient_label' => $category->recipient_label,
                'cover_text' => $category->cover_text,
                'invitation_text' => $updated,
                'closing_text' => $category->closing_text,
                'access_mode' => $category->access_mode,
                'rsvp_enabled' => 1,
            ])
            ->assertRedirect(route('admin.categories.edit', $category));

        $this->assertSame($updated, $category->fresh()->invitation_text);
    }
}
