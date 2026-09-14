<?php

namespace Tests\Feature;

use App\Models\InvitationCategory;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvitationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{ms: float, bytes: int, queries: int}
     */
    private function measureGet(string $uri): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = hrtime(true);
        $response = $this->get($uri);
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $response->assertOk();

        return [
            'ms' => round($elapsedMs, 2),
            'bytes' => strlen($response->getContent() ?? ''),
            'queries' => count(DB::getQueryLog()),
        ];
    }

    public function test_student_gate_stays_within_performance_budget(): void
    {
        $period = $this->period();
        $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);

        $uri = '/?event='.$period->slug.'&to=yudisiawan';
        $metrics = $this->measureGet($uri);

        $this->assertStringContainsString('invitation.js', $this->get($uri)->getContent() ?? '');
        $this->assertLessThanOrEqual(10, $metrics['queries'], 'Query gate mahasiswa: '.$metrics['queries']);
        $this->assertLessThanOrEqual(85_000, $metrics['bytes'], 'HTML gate mahasiswa (bytes): '.$metrics['bytes']);
        $this->assertLessThanOrEqual(600, $metrics['ms'], 'Render gate mahasiswa (ms): '.$metrics['ms']);
    }

    public function test_formal_student_invitation_stays_within_performance_budget(): void
    {
        $period = $this->period();
        $category = $this->category($period, 'yudisiawan', InvitationCategory::ACCESS_NIM, true);
        $participant = $this->participant($period);

        $metrics = $this->measureGet('/?event='.$period->slug.'&to='.$category->slug.'&ref='.$participant->invitation_token);

        $this->assertLessThanOrEqual(18, $metrics['queries'], 'Query undangan formal: '.$metrics['queries']);
        $this->assertLessThanOrEqual(160_000, $metrics['bytes'], 'HTML undangan formal (bytes): '.$metrics['bytes']);
        $this->assertLessThanOrEqual(900, $metrics['ms'], 'Render undangan formal (ms): '.$metrics['ms']);
    }

    public function test_archive_home_stays_within_performance_budget(): void
    {
        $period = $this->period();
        $this->category($period, 'umum', InvitationCategory::ACCESS_PUBLIC);

        $metrics = $this->measureGet('/');

        $this->assertLessThanOrEqual(12, $metrics['queries'], 'Query arsip: '.$metrics['queries']);
        $this->assertLessThanOrEqual(95_000, $metrics['bytes'], 'HTML arsip (bytes): '.$metrics['bytes']);
        $this->assertLessThanOrEqual(700, $metrics['ms'], 'Render arsip (ms): '.$metrics['ms']);
    }

    public function test_static_invitation_assets_are_compact_on_disk(): void
    {
        $cssPath = public_path('css/invitation.css');
        $jsPath = public_path('js/invitation.js');

        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);

        $this->assertLessThanOrEqual(65_000, filesize($cssPath), 'CSS undangan (bytes)');
        $this->assertLessThanOrEqual(40_000, filesize($jsPath), 'JS undangan (bytes)');
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
            'recipient_label' => 'Yudisiawan / Yudisiawati',
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
