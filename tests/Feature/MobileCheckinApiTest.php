<?php

namespace Tests\Feature;

use App\Models\CheckinLog;
use App\Models\User;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileCheckinApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_download_roster(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $admin = $this->admin();

        $login = $this->postJson('/api/mobile/login', [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'Pixel panitia',
        ])->assertOk();

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->getJson('/api/mobile/events')
            ->assertOk()
            ->assertJsonPath('events.0.id', $event->id);

        $this->withToken($token)
            ->getJson('/api/mobile/events/'.$event->id.'/roster')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('participants.0.nim', $participant->nim)
            ->assertJsonPath('participants.0.qr_payload', 'YFT|'.$event->id.'|'.$participant->id.'|'.$participant->invitation_token);
    }

    public function test_non_admin_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Bukan Admin',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_offline_sync_accepts_qr_and_is_idempotent(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $token = $this->mobileToken($this->admin());
        $clientScanId = '11111111-1111-4111-8111-111111111111';
        $payload = [
            'scans' => [[
                'client_scan_id' => $clientScanId,
                'scan_code' => 'YFT|'.$event->id.'|'.$participant->id.'|'.$participant->invitation_token,
                'scanned_at' => now()->subMinute()->toIso8601String(),
            ]],
        ];

        $this->withToken($token)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'accepted')
            ->assertJsonPath('results.0.participant.nim', $participant->nim)
            ->assertJsonPath('summary.checked_in', 1);

        $this->withToken($token)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'accepted')
            ->assertJsonPath('results.0.idempotent', true);

        $this->assertNotNull($participant->fresh()->checked_in_at);
        $this->assertSame('mobile', $participant->fresh()->checkin_source);
        $this->assertSame(1, CheckinLog::query()->where('client_scan_id', $clientScanId)->count());
        $this->assertSame(1, CheckinLog::query()->where('participant_id', $participant->id)->where('status', 'accepted')->count());
    }

    public function test_second_device_sees_duplicate_when_already_checked_in(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $first = $this->mobileToken($this->admin('a'));
        $second = $this->mobileToken($this->admin('b'));

        $this->withToken($first)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', [
                'scans' => [[
                    'client_scan_id' => '22222222-2222-4222-8222-222222222222',
                    'scan_code' => $participant->nim,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'accepted');

        $this->withToken($second)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', [
                'scans' => [[
                    'client_scan_id' => '33333333-3333-4333-8333-333333333333',
                    'scan_code' => 'YFT|'.$event->id.'|'.$participant->id.'|'.$participant->invitation_token,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate')
            ->assertJsonPath('checked_in.0.id', $participant->id);
    }

    public function test_batch_sync_keeps_one_accepted_when_same_person_scanned_twice(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $token = $this->mobileToken($this->admin());
        $qr = 'YFT|'.$event->id.'|'.$participant->id.'|'.$participant->invitation_token;

        $this->withToken($token)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', [
                'scans' => [
                    [
                        'client_scan_id' => '55555555-5555-4555-8555-555555555555',
                        'scan_code' => $qr,
                    ],
                    [
                        'client_scan_id' => '66666666-6666-4666-8666-666666666666',
                        'scan_code' => $participant->nim,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'accepted')
            ->assertJsonPath('results.1.status', 'duplicate')
            ->assertJsonPath('summary.checked_in', 1);

        $this->assertSame(1, CheckinLog::query()->where('participant_id', $participant->id)->where('status', 'accepted')->count());
        $this->assertSame(1, CheckinLog::query()->where('participant_id', $participant->id)->where('status', 'duplicate')->count());
        $this->assertNotNull($participant->fresh()->checked_in_at);
    }

    public function test_claim_checkin_is_first_write_wins(): void
    {
        [, $participant] = $this->eventAndParticipant();

        $this->assertTrue($participant->claimCheckin('mobile'));
        $this->assertFalse($participant->fresh()->claimCheckin('web'));
        $this->assertSame('mobile', $participant->fresh()->checkin_source);
    }

    public function test_same_client_scan_id_twice_in_one_batch_is_replayed(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $token = $this->mobileToken($this->admin());
        $clientScanId = '77777777-7777-4777-8777-777777777777';
        $scan = [
            'client_scan_id' => $clientScanId,
            'scan_code' => $participant->nim,
        ];

        $this->withToken($token)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', [
                'scans' => [$scan, $scan],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'accepted')
            ->assertJsonPath('results.1.status', 'accepted')
            ->assertJsonPath('results.1.idempotent', true)
            ->assertJsonPath('summary.checked_in', 1);

        $this->assertSame(1, CheckinLog::query()->where('client_scan_id', $clientScanId)->count());
        $this->assertSame(1, CheckinLog::query()->where('participant_id', $participant->id)->where('status', 'accepted')->count());
    }

    public function test_mobile_sync_is_duplicate_after_web_scanner(): void
    {
        [$event, $participant] = $this->eventAndParticipant();
        $admin = $this->admin();
        $token = $this->mobileToken($admin);

        $this->actingAs($admin)
            ->postJson(route('admin.checkin.manual.scan'), [
                'period_id' => $event->id,
                'scan_code' => 'YFT|'.$event->id.'|'.$participant->id.'|'.$participant->invitation_token,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'accepted');

        $this->withToken($token)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', [
                'scans' => [[
                    'client_scan_id' => '88888888-8888-4888-8888-888888888888',
                    'scan_code' => $participant->nim,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'duplicate')
            ->assertJsonPath('summary.checked_in', 1);

        $this->assertSame(1, CheckinLog::query()->where('participant_id', $participant->id)->where('status', 'accepted')->count());
        $this->assertSame('scanner', $participant->fresh()->checkin_source);
    }

    public function test_unknown_qr_is_recorded_as_not_found(): void
    {
        [$event] = $this->eventAndParticipant();
        $token = $this->mobileToken($this->admin());

        $this->withToken($token)
            ->postJson('/api/mobile/events/'.$event->id.'/sync', [
                'scans' => [[
                    'client_scan_id' => '44444444-4444-4444-8444-444444444444',
                    'scan_code' => 'YFT|'.$event->id.'|999999|bukan-token',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'not_found');

        $this->assertDatabaseHas('checkin_logs', [
            'period_id' => $event->id,
            'status' => 'not_found',
            'source' => 'mobile',
        ]);
    }

    private function mobileToken(User $admin): string
    {
        return $this->postJson('/api/mobile/login', [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'HP uji',
        ])->json('token');
    }

    private function eventAndParticipant(): array
    {
        $event = YudisiumPeriod::create([
            'name' => 'Yudisium Mobile '.uniqid(),
            'slug' => 'yudisium-mobile-'.uniqid(),
            'event_year' => 2026,
            'event_date' => now()->toDateString(),
            'location' => 'Gedung FT',
            'is_active' => true,
            'is_published' => true,
        ]);

        $participant = YudisiumParticipant::create([
            'period_id' => $event->id,
            'nim' => '2200000099',
            'name' => 'Siti Mobile',
            'study_program' => 'Informatika',
            'checkin_status' => 'pending',
        ]);

        return [$event, $participant];
    }

    private function admin(string $suffix = ''): User
    {
        return User::create([
            'name' => 'Admin Mobile',
            'email' => 'admin-mobile-'.$suffix.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
    }
}
