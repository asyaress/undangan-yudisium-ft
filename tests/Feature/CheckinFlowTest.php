<?php

namespace Tests\Feature;

use App\Models\CheckinLog;
use App\Models\User;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CheckinFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_nim_inside_radius_checks_in(): void
    {
        [$event, $participant] = $this->eventAndParticipant();

        $this->post(route('checkin.confirm'), $this->payload($event, $participant, [
            'latitude' => -0.46905,
            'longitude' => 117.14365,
            'accuracy' => 25,
        ]))->assertOk()->assertSee('Check-in Selesai');

        $this->assertNotNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'accepted',
            'source' => 'web',
        ]);
    }

    public function test_location_outside_radius_goes_to_manual_review(): void
    {
        [$event, $participant] = $this->eventAndParticipant();

        $this->post(route('checkin.confirm'), $this->payload($event, $participant, [
            'latitude' => -0.485,
            'longitude' => 117.160,
            'accuracy' => 25,
        ]))->assertOk()->assertSee('Lokasi Perlu Diverifikasi');

        $this->assertNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'manual_review',
        ]);
    }

    public function test_bad_gps_accuracy_goes_to_manual_review(): void
    {
        [$event, $participant] = $this->eventAndParticipant();

        $this->post(route('checkin.confirm'), $this->payload($event, $participant, [
            'latitude' => -0.46905,
            'longitude' => 117.14365,
            'accuracy' => 700,
        ]))->assertOk()->assertSee('Lokasi Perlu Diverifikasi');

        $this->assertNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'manual_review',
            'message' => 'Akurasi GPS terlalu rendah.',
        ]);
    }

    public function test_missing_location_is_rejected_location(): void
    {
        [$event, $participant] = $this->eventAndParticipant();

        $this->post(route('checkin.confirm'), $this->payload($event, $participant))
            ->assertOk()
            ->assertSee('Lokasi belum terbaca');

        $this->assertNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'rejected_location',
        ]);
    }

    public function test_checkin_outside_window_logs_failed_time(): void
    {
        [$event, $participant] = $this->eventAndParticipant([
            'checkin_opens_at' => now()->subHours(3),
            'checkin_closes_at' => now()->subHour(),
        ]);

        $this->post(route('checkin.confirm'), $this->payload($event, $participant, [
            'latitude' => -0.46905,
            'longitude' => 117.14365,
            'accuracy' => 25,
        ]))->assertOk()->assertSee('Check-in Sudah Ditutup');

        $this->assertNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'failed_time',
        ]);
    }

    public function test_required_location_without_event_coordinate_blocks_checkin(): void
    {
        [$event] = $this->eventAndParticipant([
            'checkin_latitude' => null,
            'checkin_longitude' => null,
            'checkin_location_required' => true,
        ]);

        $this->get(route('checkin.form', ['slug' => $event->slug]))
            ->assertOk()
            ->assertSee('Lokasi Check-in Belum Diatur');
    }

    public function test_duplicate_confirm_only_has_one_accepted_checkin(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $payload = $this->payload($event, $participant, [
            'latitude' => -0.46905,
            'longitude' => 117.14365,
            'accuracy' => 25,
        ]);

        $this->post(route('checkin.confirm'), $payload)->assertOk();
        $firstCheckedInAt = $participant->fresh()->checked_in_at;
        $this->post(route('checkin.confirm'), $payload)->assertOk()->assertSee('Sudah Pernah Check-in');

        $this->assertEquals($firstCheckedInAt?->timestamp, $participant->fresh()->checked_in_at?->timestamp);
        $this->assertSame(1, CheckinLog::where('participant_id', $participant->id)->where('status', 'accepted')->count());
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'duplicate',
        ]);
    }

    public function test_declined_rsvp_cannot_continue_self_checkin(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $participant->forceFill([
            'rsvp_status' => 'declined',
            'rsvp_responded_at' => now(),
        ])->save();

        $this->post(route('checkin.search'), [
            'event_id' => $event->id,
            'nim' => $participant->nim,
        ])->assertOk()->assertSee('self check-in tidak tersedia');

        $this->post(route('checkin.confirm'), $this->payload($event, $participant, [
            'latitude' => -0.46905,
            'longitude' => 117.14365,
            'accuracy' => 25,
        ]))->assertOk()->assertSee('self check-in tidak tersedia');

        $this->assertNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'rejected_rsvp',
            'source' => 'web',
        ]);
    }

    public function test_admin_manual_checkin_after_manual_review_sets_checked_in_at(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $admin = $this->admin();

        $this->post(route('checkin.confirm'), $this->payload($event, $participant, [
            'latitude' => -0.485,
            'longitude' => 117.160,
            'accuracy' => 25,
        ]))->assertOk();

        $this->assertNull($participant->fresh()->checked_in_at);

        $this->actingAs($admin)->post(route('admin.checkin.manual.confirm'), [
            'period_id' => $event->id,
            'participant_id' => $participant->id,
            'nim' => $participant->nim,
            'manual_note' => 'GPS gagal, mahasiswa hadir di meja registrasi.',
        ])->assertRedirect(route('admin.checkin.manual.index', ['period_id' => $event->id]));

        $this->assertNotNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'accepted',
            'source' => 'manual',
            'admin_id' => $admin->id,
            'manual_note' => 'GPS gagal, mahasiswa hadir di meja registrasi.',
        ]);
    }

    public function test_admin_manual_duplicate_does_not_create_second_accepted_checkin(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $admin = $this->admin();
        $participant->markCheckedIn('manual');

        $this->actingAs($admin)->post(route('admin.checkin.manual.confirm'), [
            'period_id' => $event->id,
            'participant_id' => $participant->id,
            'nim' => $participant->nim,
            'manual_note' => 'Cek ulang di meja registrasi.',
        ])->assertRedirect(route('admin.checkin.manual.index', ['period_id' => $event->id]));

        $this->assertSame(0, CheckinLog::where('participant_id', $participant->id)->where('status', 'accepted')->count());
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'duplicate',
            'source' => 'manual',
            'admin_id' => $admin->id,
        ]);
    }

    public function test_admin_scanner_qr_checks_in_participant(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $admin = $this->admin();
        $qrPayload = 'YFT|'.$event->id.'|'.$participant->id.'|'.$participant->invitation_token;

        $this->actingAs($admin)
            ->postJson(route('admin.checkin.manual.scan'), [
                'period_id' => $event->id,
                'scan_code' => $qrPayload,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('participant.nim', $participant->nim)
            ->assertJsonPath('payload.summary.checked_in', 1);

        $this->assertNotNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'accepted',
            'source' => 'scanner',
            'admin_id' => $admin->id,
        ]);
    }

    public function test_admin_mobile_scanner_page_loads(): void
    {
        [$event] = $this->eventAndParticipant();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.checkin.scanner.index', ['period_id' => $event->id]))
            ->assertOk()
            ->assertSee('Scan QR Check-in')
            ->assertDontSee('navbar-brand', false)
            ->assertDontSee('left-sidebar', false)
            ->assertSee('scannerToast', false)
            ->assertSee(route('admin.checkin.manual.scan'), false)
            ->assertSee('vendor/html5-qrcode/html5-qrcode.min.js', false);
    }

    public function test_admin_scanner_accepts_manual_nim_and_live_payload(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('admin.checkin.manual.scan'), [
                'period_id' => $event->id,
                'scan_code' => $participant->nim,
                'manual_note' => 'Mahasiswa menyebutkan NIM di meja registrasi.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('payload.participants.0.checked_in', true);

        $this->actingAs($admin)
            ->getJson(route('admin.checkin.manual.live', ['period_id' => $event->id]))
            ->assertOk()
            ->assertJsonPath('summary.checked_in', 1)
            ->assertJsonPath('participants.0.nim', $participant->nim)
            ->assertJsonPath('logs.0.source', 'manual');

        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'source' => 'manual',
            'manual_note' => 'Mahasiswa menyebutkan NIM di meja registrasi.',
        ]);
    }

    public function test_location_validation_can_be_disabled(): void
    {
        [$event, $participant] = $this->eventAndParticipant([
            'checkin_location_required' => false,
            'checkin_latitude' => null,
            'checkin_longitude' => null,
        ]);

        $this->post(route('checkin.confirm'), $this->payload($event, $participant))->assertOk();

        $this->assertNotNull($participant->fresh()->checked_in_at);
        $this->assertDatabaseHas('checkin_logs', [
            'participant_id' => $participant->id,
            'status' => 'accepted',
            'source' => 'web',
        ]);
    }

    private function eventAndParticipant(array $eventOverrides = []): array
    {
        $event = YudisiumPeriod::create(array_merge([
            'name' => 'Yudisium Test '.uniqid(),
            'slug' => 'yudisium-test-'.uniqid(),
            'event_year' => 2026,
            'event_date' => now()->toDateString(),
            'location' => 'Gedung FT',
            'is_active' => true,
            'is_published' => true,
            'checkin_opens_at' => now()->subHour(),
            'checkin_closes_at' => now()->addHour(),
            'checkin_latitude' => -0.4690000,
            'checkin_longitude' => 117.1436000,
            'checkin_radius_meter' => 300,
            'checkin_location_required' => true,
        ], $eventOverrides));

        $participant = YudisiumParticipant::create([
            'period_id' => $event->id,
            'nim' => '2200000001',
            'name' => 'Ahmad Rizky',
            'study_program' => 'Informatika',
            'checkin_status' => 'pending',
        ]);

        return [$event, $participant];
    }

    private function payload(YudisiumPeriod $event, YudisiumParticipant $participant, array $overrides = []): array
    {
        return array_merge([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'nim' => $participant->nim,
        ], $overrides);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
    }
}
