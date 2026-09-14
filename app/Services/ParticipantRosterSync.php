<?php

namespace App\Services;

use App\Models\CheckinLog;
use App\Models\StudyProgram;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParticipantRosterSync
{
    public function __construct(private readonly ParticipantRosterMapper $mapper) {}

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array{saved: int, failed: int, deleted: int, missing_birth_date: int, errors: array<int, string>}
     */
    public function sync(
        YudisiumPeriod $period,
        array $records,
        bool $replaceMissing = false,
        bool $resetRsvp = false
    ): array {
        $studyPrograms = $this->activeStudyPrograms();
        $byCode = $studyPrograms->keyBy('code');
        $byName = $studyPrograms->keyBy(fn (StudyProgram $program) => $this->mapper->studyProgramLookupKey($program->name));

        $saved = 0;
        $failed = 0;
        $deleted = 0;
        $missingBirthDate = 0;
        $errors = [];
        $seenNims = [];

        DB::transaction(function () use (
            $period,
            $records,
            $replaceMissing,
            $resetRsvp,
            $byCode,
            $byName,
            &$saved,
            &$failed,
            &$deleted,
            &$missingBirthDate,
            &$errors,
            &$seenNims
        ): void {

            foreach ($records as $index => $record) {
                $rowNumber = $record['source_row'] ?? $index + 1;
                $studyProgram = $this->resolveStudyProgram($record, $byCode, $byName);

                if (! $record['sequence_number'] || ! ctype_digit((string) $record['sequence_number'])) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': no_urut wajib diisi angka.';
                    continue;
                }

                if (! $studyProgram) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': program studi '.($record['study_program'] ?: $record['study_program_code'] ?: '-').' tidak ditemukan di master prodi.';
                    continue;
                }

                if (! $record['nim'] || ! $record['name']) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': '.($record['nim'] ?: 'NIM kosong').' - nama/NIM wajib diisi.';
                    continue;
                }

                if (isset($seenNims[$record['nim']])) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': NIM '.$record['nim'].' duplikat dengan baris '.$seenNims[$record['nim']].'.';
                    continue;
                }

                $seenNims[$record['nim']] = $rowNumber;

                $birthDate = $this->mapper->parseBirthDate($record['birth_date'] ?? null);
                if (! empty($record['birth_date']) && ! $birthDate) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': '.$record['nim'].' - tanggal_lahir wajib diisi dengan format tanggal, misalnya 12-10-2007.';
                    continue;
                }

                if (! $birthDate) {
                    $missingBirthDate++;
                }

                $payload = [
                    'period_id' => $period->id,
                    'sequence_number' => (int) $record['sequence_number'],
                    'study_program_id' => $studyProgram->id,
                    'name' => $record['name'],
                    'birth_date' => $birthDate,
                    'study_program' => $studyProgram->name,
                    'faculty' => $record['faculty'] ?: 'Fakultas Teknik',
                ];

                if ($resetRsvp) {
                    $payload = [
                        ...$payload,
                        'rsvp_status' => 'pending',
                        'rsvp_note' => null,
                        'rsvp_signature' => null,
                        'rsvp_companion_count' => null,
                        'rsvp_whatsapp' => null,
                        'rsvp_proof_code' => null,
                        'rsvp_responded_at' => null,
                        'checkin_status' => 'pending',
                        'checked_in_at' => null,
                        'checkin_source' => null,
                    ];
                }

                YudisiumParticipant::updateOrCreate(
                    ['nim' => $record['nim']],
                    $payload
                );

                $saved++;
            }

            if ($replaceMissing && $seenNims !== []) {
                $stale = YudisiumParticipant::query()
                    ->where('period_id', $period->id)
                    ->whereNotIn('nim', array_keys($seenNims))
                    ->get();

                foreach ($stale as $participant) {
                    CheckinLog::query()->where('participant_id', $participant->id)->delete();
                    $participant->delete();
                    $deleted++;
                }
            }
        });

        return [
            'saved' => $saved,
            'failed' => $failed,
            'deleted' => $deleted,
            'missing_birth_date' => $missingBirthDate,
            'errors' => $errors,
        ];
    }

    /**
     * @return Collection<int, StudyProgram>
     */
    public function activeStudyPrograms(): Collection
    {
        return StudyProgram::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  Collection<string, StudyProgram>  $byCode
     * @param  Collection<string, StudyProgram>  $byName
     */
    private function resolveStudyProgram(array $record, Collection $byCode, Collection $byName): ?StudyProgram
    {
        if (! empty($record['study_program_code'])) {
            return $byCode->get($record['study_program_code']);
        }

        if (! empty($record['study_program'])) {
            return $byName->get($this->mapper->studyProgramLookupKey($record['study_program']));
        }

        return null;
    }
}
