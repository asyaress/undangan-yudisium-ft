<?php

namespace App\Services;

use App\Models\CheckinLog;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckinDesk
{
    /**
     * @return array{0: ?YudisiumParticipant, 1: string, 2: ?string}
     */
    public function resolve(YudisiumPeriod $event, string $scanCode): array
    {
        $scanCode = trim($scanCode);

        if (Str::startsWith($scanCode, 'YFT|')) {
            $parts = explode('|', $scanCode);

            if (count($parts) !== 4) {
                return [null, 'scanner', 'Format QR tidak valid.'];
            }

            [, $periodId, $participantId, $token] = $parts;

            if ((int) $periodId !== (int) $event->id) {
                return [null, 'scanner', 'QR ini bukan untuk event yang sedang dipilih.'];
            }

            $participant = YudisiumParticipant::query()
                ->with(['period', 'studyProgram'])
                ->where('period_id', $event->id)
                ->whereKey((int) $participantId)
                ->where('invitation_token', $token)
                ->first();

            return [$participant, 'scanner', $participant ? null : 'QR tidak cocok dengan data mahasiswa.'];
        }

        if (! preg_match('/^[0-9]+$/', $scanCode)) {
            return [null, 'manual', 'Masukkan NIM angka atau scan QR kartu konfirmasi.'];
        }

        $participant = YudisiumParticipant::query()
            ->with(['period', 'studyProgram'])
            ->where('period_id', $event->id)
            ->where('nim', $scanCode)
            ->first();

        return [$participant, 'manual', $participant ? null : 'NIM tidak ditemukan pada event ini.'];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{alreadyCheckedIn: bool, participant: YudisiumParticipant, status: string, idempotent?: bool}
     */
    public function store(
        YudisiumPeriod $event,
        YudisiumParticipant $participant,
        string $manualNote,
        string $source = 'manual',
        array $meta = []
    ): array {
        try {
            return DB::transaction(function () use ($event, $participant, $manualNote, $source, $meta) {
                $replay = $this->replay($meta['client_scan_id'] ?? null, $participant);
                if ($replay) {
                    return $replay;
                }

                $claimed = $participant->claimCheckin($source);
                $lockedParticipant = YudisiumParticipant::query()
                    ->with(['period', 'studyProgram'])
                    ->whereKey($participant->id)
                    ->firstOrFail();

                $status = $claimed ? 'accepted' : 'duplicate';

                $this->log($event, $lockedParticipant, [
                    'status' => $status,
                    'source' => $source,
                    'admin_id' => $meta['admin_id'] ?? null,
                    'manual_note' => $manualNote,
                    'message' => $claimed
                        ? ($source === 'scanner' || $source === 'mobile' ? 'Check-in melalui scan QR.' : 'Check-in manual oleh panitia.')
                        : 'Peserta sudah check-in sebelumnya.',
                    'ip_address' => $meta['ip_address'] ?? null,
                    'user_agent' => $meta['user_agent'] ?? null,
                    'client_scan_id' => $meta['client_scan_id'] ?? null,
                    'device_name' => $meta['device_name'] ?? null,
                    'attempted_at' => $meta['attempted_at'] ?? now(),
                ]);

                return [
                    'alreadyCheckedIn' => ! $claimed,
                    'participant' => $lockedParticipant,
                    'status' => $status,
                ];
            });
        } catch (UniqueConstraintViolationException $exception) {
            $replay = $this->replay($meta['client_scan_id'] ?? null, $participant);
            if ($replay) {
                return $replay;
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordFailedScan(
        YudisiumPeriod $event,
        ?YudisiumParticipant $participant,
        string $status,
        string $message,
        string $source = 'mobile',
        array $payload = []
    ): CheckinLog {
        $clientScanId = $payload['client_scan_id'] ?? null;

        try {
            return DB::transaction(function () use ($event, $participant, $status, $message, $source, $payload, $clientScanId) {
                if (is_string($clientScanId) && $clientScanId !== '') {
                    $existing = CheckinLog::query()->where('client_scan_id', $clientScanId)->first();
                    if ($existing) {
                        return $existing;
                    }
                }

                return $this->log($event, $participant, array_merge([
                    'status' => $status,
                    'source' => $source,
                    'message' => $message,
                    'attempted_at' => now(),
                ], $payload));
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (is_string($clientScanId) && $clientScanId !== '') {
                $existing = CheckinLog::query()->where('client_scan_id', $clientScanId)->first();
                if ($existing) {
                    return $existing;
                }
            }

            throw $exception;
        }
    }

    public function qrPayload(YudisiumParticipant $participant): string
    {
        return 'YFT|'.$participant->period_id.'|'.$participant->id.'|'.$participant->invitation_token;
    }

    /**
     * @return array<string, mixed>
     */
    public function participantCard(YudisiumParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'nim' => $participant->nim,
            'name' => $participant->name,
            'program' => $participant->studyProgram?->name ?: ($participant->study_program ?: '-'),
            'invitation_token' => $participant->invitation_token,
            'qr_payload' => $this->qrPayload($participant),
            'rsvp_status' => $participant->rsvp_status ?: 'pending',
            'checked_in' => $participant->checked_in_at !== null,
            'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
            'checkin_source' => $participant->checkin_source,
        ];
    }

    /**
     * @return array{alreadyCheckedIn: bool, participant: YudisiumParticipant, status: string, idempotent: bool}|null
     */
    private function replay(mixed $clientScanId, YudisiumParticipant $participant): ?array
    {
        if (! is_string($clientScanId) || $clientScanId === '') {
            return null;
        }

        $existing = CheckinLog::query()
            ->with(['participant.period', 'participant.studyProgram'])
            ->where('client_scan_id', $clientScanId)
            ->first();

        if (! $existing) {
            return null;
        }

        return [
            'alreadyCheckedIn' => $existing->status === 'duplicate',
            'participant' => $existing->participant ?: $participant->fresh(['period', 'studyProgram']) ?: $participant,
            'status' => $existing->status,
            'idempotent' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function log(YudisiumPeriod $event, ?YudisiumParticipant $participant, array $payload): CheckinLog
    {
        return CheckinLog::create(array_merge([
            'period_id' => $event->id,
            'participant_id' => $participant?->id,
            'nim' => $participant?->nim,
        ], $payload));
    }
}
