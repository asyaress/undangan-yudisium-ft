<?php

namespace App\Http\Controllers;

use App\Models\StudyProgram;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use App\Services\ExcelParticipantImporter;
use App\Services\ExcelTemplateExporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ParticipantImportController extends Controller
{
    public function template(ExcelTemplateExporter $exporter): BinaryFileResponse
    {
        return response()
            ->download($exporter->participantTemplate($this->activeStudyPrograms()), 'template-import-mahasiswa.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function store(Request $request, ExcelParticipantImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
        ]);

        try {
            $uploadedFile = $request->file('file');
            $rows = $importer->read($uploadedFile->getRealPath(), $uploadedFile->getClientOriginalName());
        } catch (Throwable $throwable) {
            return back()
                ->withInput()
                ->with('error', $throwable->getMessage());
        }

        if (count($rows) < 2) {
            return back()->withInput()->with('error', 'File tidak memiliki data peserta.');
        }

        $records = $this->participantRecordsFromRows($rows);
        if ($records === []) {
            return back()->withInput()->with('error', 'Data mahasiswa tidak ditemukan. Pastikan file memuat kolom NIM dan nama, atau format absensi per program studi.');
        }

        $period = YudisiumPeriod::query()->findOrFail($data['period_id']);
        $studyPrograms = $this->activeStudyPrograms();
        $studyProgramsByCode = $studyPrograms->keyBy('code');
        $studyProgramsByName = $studyPrograms->keyBy(fn (StudyProgram $program) => $this->studyProgramLookupKey($program->name));

        $saved = 0;
        $failed = 0;
        $missingBirthDate = 0;
        $errors = [];
        $seenNims = [];

        foreach ($records as $index => $record) {
            $rowNumber = $record['source_row'] ?? $index + 1;
            $studyProgram = $this->resolveStudyProgram($record, $studyProgramsByCode, $studyProgramsByName);

            if (! $record['sequence_number'] || ! ctype_digit($record['sequence_number'])) {
                $failed++;
                $errors[] = 'Baris '.$rowNumber.': no_urut wajib diisi angka.';

                continue;
            }

            if (! $studyProgram) {
                $failed++;
                $errors[] = 'Baris '.$rowNumber.': kode_prodi '.($record['study_program_code'] ?: '-').' tidak ditemukan di master prodi.';

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

            $birthDate = $this->parseBirthDate($record['birth_date']);
            if ($record['birth_date'] && ! $birthDate) {
                $failed++;
                $errors[] = 'Baris '.$rowNumber.': '.($record['nim'] ?: 'NIM kosong').' - tanggal_lahir wajib diisi dengan format tanggal, misalnya 12-10-2007.';

                continue;
            }

            if (! $birthDate) {
                $missingBirthDate++;
            }

            YudisiumParticipant::updateOrCreate(
                ['nim' => $record['nim']],
                [
                    'period_id' => $period->id,
                    'sequence_number' => (int) $record['sequence_number'],
                    'study_program_id' => $studyProgram->id,
                    'name' => $record['name'],
                    'birth_date' => $birthDate,
                    'study_program' => $studyProgram->name,
                    'faculty' => $record['faculty'],
                    'email' => null,
                    'phone' => null,
                ]
            );

            $saved++;
        }

        $message = "Import selesai. {$saved} berhasil, {$failed} gagal. Urutan file dipertahankan.";
        $warning = $missingBirthDate > 0
            ? "{$missingBirthDate} data tidak memiliki tanggal lahir. Data tetap masuk; undangan mahasiswa dibuka dengan NIM."
            : null;

        return redirect()
            ->route('admin.participants.index', ['period_id' => $period->id])
            ->with('success', $message)
            ->with('warning', $warning)
            ->with('import_errors', array_slice($errors, 0, 50));
    }

    private function participantRecordsFromRows(array $rows): array
    {
        return $this->standardParticipantRecordsFromRows($rows)
            ?: $this->attendanceParticipantRecordsFromRows($rows);
    }

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
            if (! $record['nim'] && ! $record['name']) {
                continue;
            }

            $record['source_row'] = $headerIndex + $offset + 2;
            $record['sequence_number'] = $record['sequence_number'] ?: (string) $fallbackSequence;
            $records[] = $record;
            $fallbackSequence++;
        }

        return $records;
    }

    private function attendanceParticipantRecordsFromRows(array $rows): array
    {
        $records = [];
        $currentProgram = null;
        $globalSequence = 1;

        foreach ($rows as $index => $row) {
            $singleLabel = $this->singleFilledCell($row);
            if ($singleLabel && $this->looksLikeStudyProgramLabel($singleLabel)) {
                $currentProgram = $this->normalizeStudyProgramText($singleLabel);
                continue;
            }

            if (! $this->looksLikeAttendanceParticipantRow($row)) {
                continue;
            }

            $program = $this->normalizeStudyProgramText($this->cell($row, 5) ?: $currentProgram);

            $records[] = [
                'source_row' => $index + 1,
                'sequence_number' => (string) $globalSequence,
                'study_program_code' => null,
                'nim' => $this->normalizeNim($this->cell($row, 3)),
                'name' => $this->cell($row, 2),
                'birth_date' => null,
                'study_program' => $program,
                'faculty' => 'Fakultas Teknik',
            ];

            $globalSequence++;
        }

        return $records;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = Str::of((string) $header)
                ->lower()
                ->replace(['(', ')', '.', ',', '/', '\\', '-'], ' ')
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();

            return $header;
        }, $headers);
    }

    private function mapParticipantRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            $combined[$header] = Arr::get($row, $index);
        }

        return [
            'sequence_number' => $this->pick($combined, ['no_urut', 'nomor_urut', 'urut', 'sequence_number']),
            'study_program_code' => $this->normalizeStudyProgramCode($this->pick($combined, ['kode_prodi', 'kode_program_studi', 'prodi_kode', 'code'])),
            'nim' => $this->normalizeNim($this->pick($combined, ['nim', 'nim_mahasiswa', 'npm', 'student_id'])),
            'name' => $this->pick($combined, ['nama', 'name', 'nama_mahasiswa', 'full_name']),
            'birth_date' => $this->pick($combined, ['tanggal_lahir', 'tgl_lahir', 'birth_date', 'date_of_birth']),
            'study_program' => $this->pick($combined, ['program_studi', 'prodi', 'jurusan', 'study_program']),
            'faculty' => $this->pick($combined, ['fakultas', 'faculty']),
        ];
    }

    private function activeStudyPrograms()
    {
        return StudyProgram::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    private function resolveStudyProgram(array $record, $byCode, $byName): ?StudyProgram
    {
        if ($record['study_program_code']) {
            return $byCode->get($record['study_program_code']);
        }

        if ($record['study_program']) {
            return $byName->get($this->studyProgramLookupKey($record['study_program']));
        }

        return null;
    }

    private function normalizeStudyProgramCode(?string $value): ?string
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

    private function normalizeStudyProgramText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('/^(?:D[0-9]|S[0-9])\s+/i', '', $value) ?: $value;

        return Str::title(Str::lower($value));
    }

    private function studyProgramLookupKey(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = Str::lower($value);
        $value = preg_replace('/\b(?:d[0-9]|s[0-9]|program studi|jurusan)\b/u', ' ', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?: $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?: '');
    }

    private function looksLikeStudyProgramLabel(string $value): bool
    {
        $key = $this->studyProgramLookupKey($value);

        return str_starts_with($key, 'teknik ') || in_array($key, ['arsitektur', 'informatika'], true);
    }

    private function looksLikeAttendanceParticipantRow(array $row): bool
    {
        $localNumber = $this->wholeNumberString($this->cell($row, 0));
        $nim = $this->normalizeNim($this->cell($row, 3));
        $name = $this->cell($row, 2);
        $program = $this->cell($row, 5);

        return $localNumber !== ''
            && $nim !== ''
            && strlen($nim) >= 7
            && $name !== ''
            && $program !== ''
            && preg_match('/[a-z]/i', $name) === 1;
    }

    private function singleFilledCell(array $row): ?string
    {
        $filled = array_values(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $row
        ), fn ($value) => $value !== ''));

        return count($filled) === 1 ? $filled[0] : null;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

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

    private function parseBirthDate(?string $value): ?string
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
