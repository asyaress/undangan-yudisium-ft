<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class ParticipantRosterMapper
{
    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function recordsFromRows(array $rows): array
    {
        if ($this->looksLikeAttendanceWorkbook($rows)) {
            return $this->attendanceParticipantRecordsFromRows($rows);
        }

        return $this->standardParticipantRecordsFromRows($rows)
            ?: $this->attendanceParticipantRecordsFromRows($rows);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function standardParticipantRecordsFromRows(array $rows): array
    {
        $headerIndex = null;
        $headers = [];

        foreach ($rows as $index => $row) {
            $normalizedHeaders = $this->normalizeHeaders($row);
            $hasName = in_array('nama', $normalizedHeaders, true) || in_array('name', $normalizedHeaders, true);
            $hasNim = in_array('nim', $normalizedHeaders, true)
                || in_array('nim_mahasiswa', $normalizedHeaders, true)
                || in_array('npm', $normalizedHeaders, true);

            if ($hasName && $hasNim) {
                $headerIndex = $index;
                $headers = $normalizedHeaders;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $records = [];
        $fallbackSequence = 1;

        foreach (array_slice($rows, $headerIndex + 1) as $offset => $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $record = $this->mapParticipantRow($headers, $row);
            $record['source_row'] = $headerIndex + $offset + 2;
            $record['sequence_number'] = $record['sequence_number'] ?: (string) $fallbackSequence;

            if (! $this->isUsableParticipantRecord($record)) {
                continue;
            }

            $records[] = $record;
            $fallbackSequence++;
        }

        return $records;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function attendanceParticipantRecordsFromRows(array $rows): array
    {
        $records = [];
        $currentProgram = null;

        foreach ($rows as $index => $row) {
            $singleLabel = $this->singleFilledCell($row);
            if ($singleLabel && $this->looksLikeStudyProgramLabel($singleLabel)) {
                $currentProgram = $this->normalizeStudyProgramText($singleLabel);
                continue;
            }

            if (! $this->looksLikeAttendanceParticipantRow($row)) {
                continue;
            }

            $program = $this->attendanceProgram($row, $currentProgram);
            $localSequence = $this->wholeNumberString($this->cell($row, 0));

            $records[] = [
                'source_row' => $index + 1,
                'sequence_number' => $localSequence !== '' ? $localSequence : (string) (count($records) + 1),
                'study_program_code' => null,
                'nim' => $this->attendanceNim($row),
                'name' => $this->attendanceName($row),
                'birth_date' => $this->cell($row, 6),
                'study_program' => $program,
                'faculty' => 'Fakultas Teknik',
            ];
        }

        return $records;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function looksLikeAttendanceWorkbook(array $rows): bool
    {
        foreach (array_slice($rows, 0, 8) as $row) {
            $joined = Str::upper(implode(' ', $row));

            if (str_contains($joined, 'DAFTAR HADIR') || str_contains($joined, 'YUDISIAWAN')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $row
     * @return array<string, mixed>
     */
    private function mapParticipantRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            $combined[$header] = Arr::get($row, $index);
        }

        return [
            'sequence_number' => $this->pick($combined, ['no_urut', 'nomor_urut', 'urut', 'no', 'sequence_number']),
            'study_program_code' => $this->normalizeStudyProgramCode($this->pick($combined, ['kode_prodi', 'kode_program_studi', 'prodi_kode', 'code'])),
            'nim' => $this->normalizeNim($this->pick($combined, ['nim', 'nim_mahasiswa', 'npm', 'student_id'])),
            'name' => $this->pick($combined, ['nama', 'name', 'nama_mahasiswa', 'full_name']),
            'birth_date' => $this->pick($combined, ['tanggal_lahir', 'tgl_lahir', 'birth_date', 'date_of_birth']),
            'study_program' => $this->normalizeStudyProgramText($this->pick($combined, ['program_studi', 'prodi', 'jurusan', 'study_program'])),
            'faculty' => $this->pick($combined, ['fakultas', 'faculty']),
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function isUsableParticipantRecord(array $record): bool
    {
        $nim = $this->normalizeNim($record['nim'] ?? null);
        $name = trim((string) ($record['name'] ?? ''));

        return $this->isValidNim($nim)
            && $this->isPersonName($name);
    }

    /**
     * @param  array<int, string>  $row
     */
    private function looksLikeAttendanceParticipantRow(array $row): bool
    {
        $localNumber = $this->wholeNumberString($this->cell($row, 0));
        $nim = $this->attendanceNim($row);
        $name = $this->attendanceName($row);

        return $localNumber !== ''
            && $this->isValidNim($nim)
            && $this->isPersonName($name);
    }

    /**
     * @param  array<int, string>  $row
     */
    private function attendanceNim(array $row): ?string
    {
        foreach ([5, 3] as $index) {
            $nim = $this->normalizeNim($this->cell($row, $index));
            if ($this->isValidNim($nim)) {
                return $nim;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function attendanceName(array $row): ?string
    {
        foreach ([2, 1] as $index) {
            $name = $this->cell($row, $index);
            if ($this->isPersonName($name)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function attendanceProgram(array $row, ?string $currentProgram): ?string
    {
        foreach ([7, 5] as $index) {
            $value = $this->cell($row, $index);
            if ($value && $this->looksLikeStudyProgramLabel($value)) {
                return $this->normalizeStudyProgramText($value);
            }
        }

        return $currentProgram;
    }

    public function parseBirthDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y', 'd.m.y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                continue;
            }
        }

        if (is_numeric($value)) {
            $wholeNumber = $this->wholeNumberString($value);

            if ($compactDate = $this->parseCompactBirthDate($wholeNumber)) {
                return $compactDate;
            }

            $serial = (int) floor((float) $value);
            if ($serial > 0 && $serial < 80000) {
                return CarbonImmutable::create(1899, 12, 30)
                    ->addDays($serial)
                    ->toDateString();
            }
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    public function normalizeStudyProgramCode(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '') {
            return null;
        }

        return str_pad($digits, 2, '0', STR_PAD_LEFT);
    }

    public function studyProgramLookupKey(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = Str::lower($value);
        $value = preg_replace('/\b(?:d[0-9]|s[0-9]|program studi|jurusan)\b/u', ' ', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }

    public function normalizeStudyProgramText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('/^(?:D[0-9]|S[0-9])\s+/i', '', $value) ?: $value;

        return Str::title(Str::lower($value));
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return Str::of((string) $header)
                ->lower()
                ->replace(['(', ')', '.', ',', '/', '\\', '-'], ' ')
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
        }, $headers);
    }

    private function looksLikeStudyProgramLabel(string $value): bool
    {
        $key = $this->studyProgramLookupKey($value);

        return str_starts_with($key, 'teknik ')
            || in_array($key, ['arsitektur', 'informatika', 'sistem informasi'], true);
    }

    private function isValidNim(?string $value): bool
    {
        return is_string($value) && preg_match('/^\d{7,20}$/', $value) === 1;
    }

    private function isPersonName(?string $value): bool
    {
        $name = trim((string) $value);
        if ($name === '' || preg_match('/[a-z]/i', $name) !== 1) {
            return false;
        }

        return ! in_array(Str::upper($name), ['NAMA', 'NAME', 'NAMA MAHASISWA'], true);
    }

    /**
     * @param  array<int, string>  $row
     */
    private function singleFilledCell(array $row): ?string
    {
        $filled = array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $row
        ), fn ($value) => $value !== ''));

        return count($filled) === 1 ? $filled[0] : null;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function cell(array $row, int $index): ?string
    {
        $value = trim((string) Arr::get($row, $index, ''));

        return $value === '' ? null : $value;
    }

    private function normalizeNim(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = $this->wholeNumberString($value);

        return $digits !== '' ? $digits : trim($value);
    }

    private function wholeNumberString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function parseCompactBirthDate(string $value): ?string
    {
        $candidates = [];

        if (preg_match('/^\d{8}$/', $value)) {
            $candidates[] = $value;
        }

        if (preg_match('/^\d{7}$/', $value)) {
            $candidates[] = '0'.$value;
        }

        foreach ($candidates as $candidate) {
            foreach (['dmY', 'Ymd'] as $format) {
                try {
                    $date = CarbonImmutable::createFromFormat($format, $candidate);
                    if ($date && $date->format($format) === $candidate) {
                        return $date->toDateString();
                    }
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function pick(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
