<?php

namespace App\Http\Controllers;

use App\Models\CheckinLog;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckinController extends Controller
{
    private const MAX_ACCEPTED_ACCURACY_METERS = 500;

    public function index(Request $request, ?string $slug = null): View
    {
        $event = $this->resolveEvent($slug);
        $checkinStatus = $this->effectiveCheckinStatus($event);

        return $this->publicView($event, [
            'step' => $checkinStatus === 'open' ? 'intro' : 'blocked',
            'checkinStatus' => $checkinStatus,
        ]);
    }

    public function search(Request $request): View
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'nim' => ['required', 'regex:/^[0-9]+$/', 'max:20'],
        ], [
            'nim.regex' => 'NIM hanya boleh berisi angka.',
        ]);

        $event = YudisiumPeriod::query()->findOrFail($data['event_id']);
        $checkinStatus = $this->effectiveCheckinStatus($event);
        if ($checkinStatus !== 'open') {
            return $this->publicView($event, ['step' => 'blocked', 'checkinStatus' => $checkinStatus]);
        }

        $participant = YudisiumParticipant::query()
            ->with(['period', 'studyProgram'])
            ->where('period_id', $event->id)
            ->where('nim', $data['nim'])
            ->first();

        if (! $participant) {
            return $this->publicView($event, [
                'step' => 'nim',
                'lookupNim' => $data['nim'],
                'lookupError' => 'NIM tidak ditemukan dalam daftar peserta yudisium.',
            ]);
        }

        return $this->publicView($event, [
            'step' => 'identity',
            'participant' => $participant,
            'lookupNim' => $data['nim'],
        ]);
    }

    public function confirm(Request $request): View
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'participant_id' => ['required', 'integer', 'exists:yudisium_participants,id'],
            'nim' => ['required', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $event = YudisiumPeriod::query()->findOrFail($data['event_id']);
        $participant = YudisiumParticipant::query()
            ->with(['period', 'studyProgram'])
            ->whereKey($data['participant_id'])
            ->where('period_id', $event->id)
            ->firstOrFail();

        if ($participant->nim !== $data['nim']) {
            return $this->publicView($event, [
                'step' => 'nim',
                'lookupError' => 'NIM tidak cocok dengan data yang ditemukan.',
            ]);
        }

        $checkinStatus = $this->effectiveCheckinStatus($event);
        if ($checkinStatus !== 'open') {
            $this->logAttempt($request, $event, $participant, [
                'status' => in_array($checkinStatus, ['not_open', 'closed'], true) ? 'failed_time' : 'rejected_location',
                'source' => 'web',
                'message' => $checkinStatus === 'not_open'
                    ? 'Percobaan check-in sebelum waktu dibuka.'
                    : ($checkinStatus === 'closed'
                        ? 'Percobaan check-in setelah waktu ditutup.'
                        : 'Konfigurasi lokasi check-in belum lengkap.'),
            ]);

            return $this->publicView($event, [
                'step' => 'blocked',
                'participant' => $participant,
                'checkinStatus' => $checkinStatus,
            ]);
        }

        if ($participant->checked_in_at) {
            $this->logAttempt($request, $event, $participant, [
                'status' => 'duplicate',
                'source' => 'web',
                'message' => 'Peserta sudah check-in sebelumnya.',
            ]);

            return $this->publicView($event, [
                'step' => 'success',
                'participant' => $participant,
                'alreadyCheckedIn' => true,
            ]);
        }

        $radius = (int) ($event->checkin_radius_meter ?: 300);
        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $accuracy = isset($data['accuracy']) ? (int) round((float) $data['accuracy']) : null;
        $distance = null;
        $manualReviewMessage = null;

        if ($event->checkin_location_required && $event->hasCheckinCoordinate()) {
            if ($latitude === null || $longitude === null) {
                $this->logAttempt($request, $event, $participant, [
                    'status' => 'rejected_location',
                    'source' => 'web',
                    'radius_meter' => $radius,
                    'message' => 'Lokasi perangkat tidak diterima.',
                ]);

                return $this->publicView($event, [
                    'step' => 'location',
                    'participant' => $participant,
                    'locationError' => 'Lokasi belum terbaca. Izinkan akses lokasi lalu coba lagi.',
                ]);
            }

            $distance = $this->distanceMeters(
                (float) $event->checkin_latitude,
                (float) $event->checkin_longitude,
                $latitude,
                $longitude
            );

            if ($distance > $radius) {
                $manualReviewMessage = 'Lokasi di luar radius check-in.';
            } elseif ($accuracy === null || $accuracy > self::MAX_ACCEPTED_ACCURACY_METERS) {
                $manualReviewMessage = 'Akurasi GPS terlalu rendah.';
            }
        }

        if ($manualReviewMessage) {
            $this->logAttempt($request, $event, $participant, [
                'status' => 'manual_review',
                'source' => 'web',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_meter' => $distance,
                'accuracy_meter' => $accuracy,
                'radius_meter' => $radius,
                'message' => $manualReviewMessage,
            ]);

            return $this->publicView($event, [
                'step' => 'manual_review',
                'participant' => $participant,
                'distance' => $distance,
                'accuracy' => $accuracy,
                'radius' => $radius,
            ]);
        }

        $result = DB::transaction(function () use ($request, $event, $participant, $latitude, $longitude, $distance, $accuracy, $radius) {
            $lockedParticipant = YudisiumParticipant::query()
                ->with(['period', 'studyProgram'])
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedParticipant->checked_in_at) {
                $this->logAttempt($request, $event, $lockedParticipant, [
                    'status' => 'duplicate',
                    'source' => 'web',
                    'message' => 'Peserta sudah check-in sebelumnya.',
                ]);

                return ['participant' => $lockedParticipant, 'alreadyCheckedIn' => true];
            }

            $lockedParticipant->markCheckedIn('web-location');
            $lockedParticipant->refresh();

            $this->logAttempt($request, $event, $lockedParticipant, [
                'status' => 'accepted',
                'source' => 'web',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_meter' => $distance,
                'accuracy_meter' => $accuracy,
                'radius_meter' => $event->hasCheckinCoordinate() ? $radius : null,
                'message' => 'Check-in berhasil.',
            ]);

            return ['participant' => $lockedParticipant, 'alreadyCheckedIn' => false];
        });

        return $this->publicView($event, [
            'step' => 'success',
            'participant' => $result['participant'],
            'distance' => $distance,
            'accuracy' => $accuracy,
            'alreadyCheckedIn' => $result['alreadyCheckedIn'],
        ]);
    }

    public function manualIndex(Request $request): View
    {
        $event = $this->adminEvent($request);

        return view('admin.checkin.manual', [
            'period' => $event,
            'participant' => null,
            'lookupNim' => null,
            'lookupError' => null,
            'logs' => $this->recentLogs($event),
        ]);
    }

    public function manualSearch(Request $request): View
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'nim' => ['required', 'string', 'max:20'],
        ]);

        $event = YudisiumPeriod::query()->findOrFail($data['period_id']);
        $participant = YudisiumParticipant::query()
            ->with(['period', 'studyProgram'])
            ->where('period_id', $event->id)
            ->where('nim', $data['nim'])
            ->first();

        return view('admin.checkin.manual', [
            'period' => $event,
            'participant' => $participant,
            'lookupNim' => $data['nim'],
            'lookupError' => $participant ? null : 'Data peserta dengan NIM tersebut tidak ditemukan.',
            'logs' => $this->recentLogs($event),
        ]);
    }

    public function manualConfirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'participant_id' => ['required', 'integer', 'exists:yudisium_participants,id'],
            'nim' => ['required', 'string', 'max:20'],
            'manual_note' => ['required', 'string', 'max:500'],
        ]);

        $event = YudisiumPeriod::query()->findOrFail($data['period_id']);
        $participant = YudisiumParticipant::query()
            ->where('period_id', $event->id)
            ->whereKey($data['participant_id'])
            ->firstOrFail();

        if ($participant->nim !== $data['nim']) {
            return back()->withInput()->with('error', 'NIM tidak cocok dengan data yang ditemukan.');
        }

        $result = DB::transaction(function () use ($request, $event, $participant, $data) {
            $lockedParticipant = YudisiumParticipant::query()
                ->whereKey($participant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyCheckedIn = (bool) $lockedParticipant->checked_in_at;
            if (! $alreadyCheckedIn) {
                $lockedParticipant->markCheckedIn('manual');
            }

            $this->logAttempt($request, $event, $lockedParticipant, [
                'status' => $alreadyCheckedIn ? 'duplicate' : 'accepted',
                'source' => 'manual',
                'admin_id' => $request->user()?->id,
                'manual_note' => $data['manual_note'],
                'message' => $alreadyCheckedIn
                    ? 'Peserta sudah check-in sebelumnya.'
                    : 'Check-in manual oleh panitia.',
            ]);

            return [
                'alreadyCheckedIn' => $alreadyCheckedIn,
                'participant' => $lockedParticipant,
            ];
        });

        return redirect()
            ->route('admin.checkin.manual.index', ['period_id' => $event->id])
            ->with($result['alreadyCheckedIn'] ? 'warning' : 'success', $result['alreadyCheckedIn']
                ? 'Peserta sudah check-in sebelumnya.'
                : "Check-in manual berhasil untuk {$result['participant']->name}.");
    }

    private function publicView(?YudisiumPeriod $event, array $data = []): View
    {
        return view('checkin.index', array_merge([
            'event' => $event,
            'step' => 'intro',
            'participant' => null,
            'lookupNim' => null,
            'lookupError' => null,
            'locationError' => null,
            'distance' => null,
            'accuracy' => null,
            'radius' => $event?->checkin_radius_meter ?: 300,
            'alreadyCheckedIn' => false,
            'checkinStatus' => $event?->checkinStatus() ?? 'no_event',
        ], $data));
    }

    private function resolveEvent(?string $slug): ?YudisiumPeriod
    {
        if ($slug) {
            return YudisiumPeriod::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->first();
        }

        return YudisiumPeriod::query()
            ->where('is_active', true)
            ->where('is_published', true)
            ->latest('updated_at')
            ->first();
    }

    private function effectiveCheckinStatus(?YudisiumPeriod $event): string
    {
        if (! $event) {
            return 'no_event';
        }

        if ($event->checkin_location_required && ! $event->hasCheckinCoordinate()) {
            return 'location_unset';
        }

        return $event->checkinStatus();
    }

    private function adminEvent(Request $request): ?YudisiumPeriod
    {
        $periodId = $request->integer('period_id')
            ?: YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');

        return $periodId ? YudisiumPeriod::query()->find($periodId) : null;
    }

    private function recentLogs(?YudisiumPeriod $event)
    {
        return CheckinLog::query()
            ->with(['participant.studyProgram', 'period', 'admin'])
            ->when($event, fn ($query) => $query->where('period_id', $event->id))
            ->latest('attempted_at')
            ->limit(50)
            ->get();
    }

    private function logAttempt(Request $request, YudisiumPeriod $event, ?YudisiumParticipant $participant, array $payload): void
    {
        CheckinLog::create(array_merge([
            'period_id' => $event->id,
            'participant_id' => $participant?->id,
            'nim' => $participant?->nim,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'attempted_at' => now(),
        ], $payload));
    }

    private function distanceMeters(float $latA, float $lngA, float $latB, float $lngB): int
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($latA);
        $latTo = deg2rad($latB);
        $latDelta = deg2rad($latB - $latA);
        $lngDelta = deg2rad($lngB - $lngA);

        $angle = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($angle), sqrt(1 - $angle)));
    }
}
