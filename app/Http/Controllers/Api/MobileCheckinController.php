<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileDeviceToken;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use App\Services\CheckinDesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileCheckinController extends Controller
{
    public function __construct(private CheckinDesk $desk) {}

    public function events(): JsonResponse
    {
        $events = YudisiumPeriod::query()
            ->withCount(['participants as participant_count'])
            ->orderByDesc('is_active')
            ->orderByDesc('event_date')
            ->get()
            ->map(fn (YudisiumPeriod $period) => $this->eventPayload($period));

        return response()->json([
            'events' => $events,
        ]);
    }

    public function roster(YudisiumPeriod $period): JsonResponse
    {
        $participants = YudisiumParticipant::query()
            ->with('studyProgram')
            ->where('period_id', $period->id)
            ->orderBy('name')
            ->get();

        $summary = $this->desk->summary($period);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'event' => $this->eventPayload($period),
            'summary' => $summary,
            'participants' => $participants
                ->map(fn (YudisiumParticipant $participant) => $this->desk->participantCard($participant))
                ->values(),
        ]);
    }

    public function scan(Request $request, YudisiumPeriod $period): JsonResponse
    {
        $scan = $request->validate([
            'client_scan_id' => ['required', 'uuid'],
            'scan_code' => ['required', 'string', 'max:500'],
            'scanned_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $device = $request->attributes->get('mobileDevice');
        $deviceName = $device instanceof MobileDeviceToken ? $device->name : null;

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'result' => $this->syncOne($request, $period, $scan, $deviceName),
            'summary' => $this->desk->summary($period),
        ]);
    }

    public function sync(Request $request, YudisiumPeriod $period): JsonResponse
    {
        $data = $request->validate([
            'scans' => ['present', 'array', 'max:200'],
            'scans.*.client_scan_id' => ['required', 'uuid'],
            'scans.*.scan_code' => ['required', 'string', 'max:500'],
            'scans.*.scanned_at' => ['nullable', 'date'],
            'scans.*.note' => ['nullable', 'string', 'max:500'],
            'include_checked_in' => ['sometimes', 'boolean'],
        ]);

        $device = $request->attributes->get('mobileDevice');
        $deviceName = $device instanceof MobileDeviceToken ? $device->name : null;
        $results = [];

        foreach ($data['scans'] as $scan) {
            $results[] = $this->syncOne(
                $request,
                $period,
                $scan,
                $deviceName
            );
        }

        $summary = $this->desk->summary($period);
        $includeCheckedIn = $request->boolean('include_checked_in');

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'results' => $results,
            'checked_in' => $includeCheckedIn ? $this->checkedInList($period) : [],
            'summary' => $summary,
        ]);
    }

    /**
     * @return array<int, array{id: int, checked_in_at: string|null, checkin_source: string|null}>
     */
    private function checkedInList(YudisiumPeriod $period): array
    {
        return YudisiumParticipant::query()
            ->where('period_id', $period->id)
            ->whereNotNull('checked_in_at')
            ->orderBy('checked_in_at')
            ->get(['id', 'checked_in_at', 'checkin_source'])
            ->map(fn (YudisiumParticipant $participant) => [
                'id' => $participant->id,
                'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
                'checkin_source' => $participant->checkin_source,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{client_scan_id: string, scan_code: string, scanned_at?: string|null, note?: string|null}  $scan
     * @return array<string, mixed>
     */
    private function syncOne(Request $request, YudisiumPeriod $period, array $scan, ?string $deviceName): array
    {
        $clientScanId = $scan['client_scan_id'];
        $scanCode = trim($scan['scan_code']);
        $note = trim((string) ($scan['note'] ?? '')) ?: 'Scan dari aplikasi HP.';
        $attemptedAt = isset($scan['scanned_at']) ? Carbon::parse($scan['scanned_at']) : now();

        [$participant, , $error] = $this->desk->resolve($period, $scanCode);

        $meta = [
            'admin_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'client_scan_id' => $clientScanId,
            'device_name' => $deviceName,
            'attempted_at' => $attemptedAt,
        ];

        if (! $participant) {
            $this->desk->recordFailedScan(
                $period,
                null,
                'not_found',
                $error ?: 'Data mahasiswa tidak ditemukan.',
                'mobile',
                $meta
            );

            return [
                'client_scan_id' => $clientScanId,
                'status' => 'not_found',
                'message' => $error ?: 'Data mahasiswa tidak ditemukan.',
                'participant' => null,
            ];
        }

        $result = $this->desk->store($period, $participant, $note, 'mobile', $meta);
        $card = $this->desk->participantCard($result['participant']);

        return [
            'client_scan_id' => $clientScanId,
            'status' => $result['status'],
            'message' => $result['status'] === 'duplicate'
                ? 'Mahasiswa ini sudah check-in sebelumnya.'
                : 'Check-in berhasil.',
            'idempotent' => (bool) ($result['idempotent'] ?? false),
            'participant' => $card,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(YudisiumPeriod $period): array
    {
        return [
            'id' => $period->id,
            'name' => $period->name,
            'slug' => $period->slug,
            'event_date' => $period->event_date?->toDateString(),
            'location' => $period->location,
            'is_active' => (bool) $period->is_active,
            'participant_count' => (int) ($period->participant_count ?? 0) ?: $period->participants()->count(),
        ];
    }
}
