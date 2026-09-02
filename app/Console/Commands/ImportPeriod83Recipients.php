<?php

namespace App\Console\Commands;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumPeriod;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ImportPeriod83Recipients extends Command
{
    protected $signature = 'yudisium:import-period83-recipients
        {--period=yudisium-tahun-2026-angkatan-83-periode-3 : Slug periode tujuan}
        {--officials=DAFTAR NAMA PEJABAT, KPS, KALAB FT 2024 AGUSTUS 2026 (1).xlsx : File pejabat/KPS/Kalab}
        {--employees=Data Pegawai FT Unmul 2026.xlsx : File tendik/keamanan/kebersihan}
        {--keep-missing : Jangan hapus penerima lama yang tidak ada di Excel}';

    protected $description = 'Import data pejabat, KPS, Kalab, tendik, keamanan, dan CS untuk periode 83 periode 3.';

    public function handle(): int
    {
        $period = YudisiumPeriod::query()
            ->where('slug', (string) $this->option('period'))
            ->first();

        if (! $period) {
            $this->error('Periode tujuan tidak ditemukan.');

            return self::FAILURE;
        }

        $officialsPath = $this->workbookPath((string) $this->option('officials'));
        $employeesPath = $this->workbookPath((string) $this->option('employees'));

        if (! is_file($officialsPath) || ! is_file($employeesPath)) {
            $this->error('File Excel tidak ditemukan. Pastikan file berada di root project atau isi option --officials/--employees.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($period, $officialsPath, $employeesPath): void {
            $categories = $this->ensureCategories($period);
            $officials = $this->readWorkbook($officialsPath);
            $employees = $this->readWorkbook($employeesPath);

            $imports = [
                'pejabat' => $this->officialRows($employees['PEJABAT'] ?? $officials['PEJABAT FT 2024'] ?? []),
                'kps' => $this->officialRows($officials['KPS'] ?? []),
                'kalab' => $this->officialRows($officials['KALAB'] ?? []),
                'tendik' => [
                    ...$this->tendikPnsRows($employees['TENDIK PNS'] ?? []),
                    ...$this->tendikPppkRows($employees['TENDIK PPPK'] ?? []),
                    ...$this->tendikBluRows($employees['TENDIK PROFESIONAL BLU'] ?? []),
                ],
                'tenaga-keamanan' => $this->nameOnlyRows(
                    $employees['TENAGA KEAMANAN'] ?? [],
                    'Tenaga Keamanan',
                    'Tenaga Keamanan Fakultas Teknik'
                ),
                'tenaga-cs' => $this->nameOnlyRows(
                    $employees['TENAGA KEBERSIHAN'] ?? [],
                    'Tenaga Cleaning Service',
                    'Tenaga Cleaning Service Fakultas Teknik'
                ),
            ];

            foreach ($imports as $slug => $records) {
                $summary = $this->saveRecipients($period, $categories[$slug], $records);

                $this->line(sprintf(
                    '%s: %d baru, %d diperbarui, %d dihapus, %d dilewati.',
                    $categories[$slug]->title,
                    $summary['created'],
                    $summary['updated'],
                    $summary['deleted'],
                    $summary['skipped'],
                ));
            }

            $staleLegacyRecords = $this->removeStaleLegacyRecipients($period, $imports);
            if ($staleLegacyRecords > 0) {
                $this->line('Kategori senat/manual: '.$staleLegacyRecords.' data lama yang bentrok dengan XLSX dibersihkan.');
            }
        });

        $this->info('Import periode 83 selesai.');

        return self::SUCCESS;
    }

    private function workbookPath(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @return array<string, array<int, array<int, string>>>
     */
    private function readWorkbook(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak bisa dibuka: '.$path);
        }

        $sharedStrings = $this->loadSharedStrings($zip);
        $targets = $this->sheetTargets($zip);
        $sheets = [];

        foreach ($targets as $sheetName => $target) {
            $sheetXml = $zip->getFromName($target);

            if ($sheetXml === false) {
                continue;
            }

            $sheets[$sheetName] = $this->readSheetRows($sheetXml, $sharedStrings);
        }

        $zip->close();

        return $sheets;
    }

    /**
     * @return array<string>
     */
    private function loadSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];
        foreach ($xpath->query('//main:si') as $sharedString) {
            $text = '';

            foreach ($xpath->query('.//main:t', $sharedString) as $textNode) {
                $text .= $textNode->textContent;
            }

            $strings[] = $this->clean($text);
        }

        return $strings;
    }

    /**
     * @return array<string, string>
     */
    private function sheetTargets(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            throw new RuntimeException('Struktur XLSX tidak valid.');
        }

        $workbookDom = new DOMDocument;
        $workbookDom->loadXML($workbookXml);
        $workbookXpath = new DOMXPath($workbookDom);
        $workbookXpath->registerNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbookXpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $relsDom = new DOMDocument;
        $relsDom->loadXML($relsXml);
        $relsXpath = new DOMXPath($relsDom);
        $relsXpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $targets = [];
        foreach ($workbookXpath->query('//main:sheets/main:sheet') as $sheetNode) {
            $name = $sheetNode->attributes?->getNamedItem('name')?->nodeValue ?? '';
            $relId = $sheetNode->attributes?->getNamedItemNS(
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                'id'
            )?->nodeValue ?? '';

            if ($name === '' || $relId === '') {
                continue;
            }

            $target = $relsXpath->evaluate("string(//rel:Relationship[@Id='{$relId}']/@Target)");

            if ($target !== '') {
                $targets[$name] = $this->normalizeSheetTarget($target);
            }
        }

        return $targets;
    }

    private function normalizeSheetTarget(string $target): string
    {
        $target = str_replace('\\', '/', $target);

        if (str_starts_with($target, '/xl/')) {
            return ltrim($target, '/');
        }

        if (str_starts_with($target, 'xl/')) {
            return $target;
        }

        return 'xl/'.ltrim($target, '/');
    }

    /**
     * @param  array<string>  $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function readSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $dom = new DOMDocument;
        $dom->loadXML($sheetXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];
        foreach ($xpath->query('//main:sheetData/main:row') as $rowNode) {
            $rowValues = [];

            foreach ($xpath->query('./main:c', $rowNode) as $cellNode) {
                $ref = $cellNode->attributes?->getNamedItem('r')?->nodeValue ?? '';
                $type = $cellNode->attributes?->getNamedItem('t')?->nodeValue ?? '';
                $columnIndex = $this->columnIndexFromCellRef($ref);
                $rowValues[$columnIndex] = $this->readCellValue($xpath, $cellNode, $type, $sharedStrings);
            }

            if ($this->rowHasValue($rowValues)) {
                ksort($rowValues);
                $maxColumn = max(array_keys($rowValues));
                $rows[] = array_map(
                    fn (int $index): string => $rowValues[$index] ?? '',
                    range(0, $maxColumn)
                );
            }
        }

        return $rows;
    }

    /**
     * @param  array<string>  $sharedStrings
     */
    private function readCellValue(DOMXPath $xpath, DOMNode $cellNode, string $type, array $sharedStrings): string
    {
        if ($type === 'inlineStr') {
            $text = '';

            foreach ($xpath->query('.//main:t', $cellNode) as $textNode) {
                $text .= $textNode->textContent;
            }

            return $this->clean($text);
        }

        $valueNode = $xpath->query('./main:v', $cellNode)->item(0);
        if (! $valueNode) {
            return '';
        }

        $value = (string) $valueNode->textContent;

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $this->clean($value);
    }

    private function columnIndexFromCellRef(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');

        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @return array<string, InvitationCategory>
     */
    private function ensureCategories(YudisiumPeriod $period): array
    {
        $definitions = [
            'pejabat' => ['Pejabat Fakultas dan Universitas', 'Pejabat Fakultas dan Universitas', InvitationCategory::ACCESS_PRIVATE, 3],
            'kps' => ['Koordinator Program Studi', 'Koordinator Program Studi Fakultas Teknik', InvitationCategory::ACCESS_PRIVATE, 4],
            'kalab' => ['Kepala Laboratorium', 'Kepala Laboratorium Fakultas Teknik', InvitationCategory::ACCESS_PRIVATE, 5],
            'tendik' => ['Tenaga Kependidikan', 'Tenaga Kependidikan Fakultas Teknik', InvitationCategory::ACCESS_NIP, 8],
            'tenaga-cs' => ['Tenaga Cleaning Service', 'Tenaga Cleaning Service Fakultas Teknik', InvitationCategory::ACCESS_NAME, 9],
            'tenaga-keamanan' => ['Tenaga Keamanan', 'Tenaga Keamanan Fakultas Teknik', InvitationCategory::ACCESS_NAME, 10],
        ];

        $categories = [];

        foreach ($definitions as $slug => [$title, $recipientLabel, $accessMode, $sortOrder]) {
            $categories[$slug] = InvitationCategory::query()->updateOrCreate(
                [
                    'period_id' => $period->id,
                    'slug' => $slug,
                ],
                [
                    'title' => $title,
                    'recipient_label' => $recipientLabel,
                    'cover_text' => 'Program Sarjana Angkatan 83 Periode 3 Tahun 2026.',
                    'invitation_text' => $this->invitationText($title),
                    'closing_text' => 'Atas perhatian dan kehadirannya, kami ucapkan terima kasih.',
                    'sort_order' => $sortOrder,
                    'access_mode' => $accessMode,
                    'rsvp_enabled' => true,
                ],
            );
        }

        $this->mergeLegacyCategory($period, 'satpam', $categories['tenaga-keamanan']);
        $this->mergeLegacyCategory($period, 'cs', $categories['tenaga-cs']);

        return $categories;
    }

    private function mergeLegacyCategory(YudisiumPeriod $period, string $legacySlug, InvitationCategory $target): void
    {
        $legacy = InvitationCategory::query()
            ->where('period_id', $period->id)
            ->where('slug', $legacySlug)
            ->first();

        if (! $legacy || (int) $legacy->id === (int) $target->id) {
            return;
        }

        InvitationRecipient::query()
            ->where('period_id', $period->id)
            ->where('category_id', $legacy->id)
            ->update(['category_id' => $target->id]);

        $legacy->delete();
    }

    private function invitationText(string $title): string
    {
        return 'Dengan hormat, kami mengundang '.$title.' Fakultas Teknik Universitas Mulawarman untuk menghadiri prosesi Yudisium Program Sarjana Angkatan 83 Periode 3 Tahun 2026.';
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, ?string>>
     */
    private function officialRows(array $rows): array
    {
        $records = [];

        foreach (array_slice($rows, 3) as $row) {
            $name = $this->clean($this->cell($row, 1));
            $position = $this->clean($this->cell($row, 6));

            if ($name === '' || $position === '') {
                continue;
            }

            $records[] = $this->record(
                $name,
                $this->cleanIdentifier($this->cell($row, 2)),
                $position,
                $position
            );
        }

        return $records;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, ?string>>
     */
    private function tendikPnsRows(array $rows): array
    {
        $records = [];

        foreach (array_slice($rows, 3) as $row) {
            $name = $this->clean($this->cell($row, 1));
            $position = $this->clean($this->cell($row, 4));

            if ($name === '') {
                continue;
            }

            $context = $this->joinContext([
                $position,
                $this->clean($this->cell($row, 5)),
                $this->clean($this->cell($row, 6)),
            ]);

            $records[] = $this->record($name, $this->cleanIdentifier($this->cell($row, 2)), $position, $context);
        }

        return $records;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, ?string>>
     */
    private function tendikPppkRows(array $rows): array
    {
        $records = [];

        foreach (array_slice($rows, 3) as $row) {
            $name = $this->clean($this->cell($row, 1));
            $position = $this->clean($this->cell($row, 4));

            if ($name === '') {
                continue;
            }

            $context = $this->joinContext([
                $position,
                $this->clean($this->cell($row, 5)),
                $this->clean($this->cell($row, 6)),
            ]);

            $records[] = $this->record($name, $this->cleanIdentifier($this->cell($row, 2)), $position, $context);
        }

        return $records;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, ?string>>
     */
    private function tendikBluRows(array $rows): array
    {
        $records = [];

        foreach (array_slice($rows, 3) as $row) {
            $name = $this->clean($this->cell($row, 1));

            if ($name === '') {
                continue;
            }

            $position = 'Tenaga Kependidikan Profesional BLU';
            $context = $this->joinContext([
                $position,
                $this->clean($this->cell($row, 3)),
                $this->clean($this->cell($row, 4)),
            ]);

            $records[] = $this->record($name, $this->cleanIdentifier($this->cell($row, 2)), $position, $context);
        }

        return $records;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, ?string>>
     */
    private function nameOnlyRows(array $rows, string $position, string $context): array
    {
        $records = [];

        foreach (array_slice($rows, 2) as $row) {
            $name = $this->clean($this->cell($row, 1));

            if ($name === '') {
                continue;
            }

            $records[] = $this->record($name, null, $position, $context);
        }

        return $records;
    }

    private function record(string $name, ?string $identifier, ?string $position, ?string $contextNote): array
    {
        return [
            'salutation' => null,
            'name' => $name,
            'display_name' => $name,
            'email' => null,
            'phone' => null,
            'identifier' => $identifier ?: null,
            'position' => $position ?: null,
            'context_note' => $contextNote ?: null,
        ];
    }

    /**
     * @param  array<int, array<string, ?string>>  $records
     * @return array{created: int, updated: int, deleted: int, skipped: int}
     */
    private function saveRecipients(YudisiumPeriod $period, InvitationCategory $category, array $records): array
    {
        $summary = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 0];
        $seen = [];
        $keptIds = [];

        if ($records === []) {
            $this->warn($category->title.': tidak ada baris dari Excel, kategori ini tidak diubah.');

            return $summary;
        }

        foreach ($records as $record) {
            $name = $this->clean($record['name'] ?? '');
            $identifier = $record['identifier'] ? $this->cleanIdentifier($record['identifier']) : null;
            $key = $identifier ?: Str::lower($name);

            if ($name === '' || isset($seen[$key])) {
                $summary['skipped']++;

                continue;
            }

            $seen[$key] = true;

            $recipient = $this->findRecipient($period, $category, $name, $identifier);
            $payload = [
                ...$record,
                'period_id' => $period->id,
                'category_id' => $category->id,
                'name' => $name,
                'display_name' => $name,
                'identifier' => $identifier,
            ];

            if ($recipient) {
                $recipient->forceFill($payload)->save();
                $keptIds[] = $recipient->id;
                $summary['updated']++;

                continue;
            }

            $created = InvitationRecipient::create([
                ...$payload,
                'rsvp_status' => 'pending',
            ]);
            $keptIds[] = $created->id;
            $summary['created']++;
        }

        if (! $this->option('keep-missing')) {
            $summary['deleted'] = InvitationRecipient::query()
                ->where('period_id', $period->id)
                ->where('category_id', $category->id)
                ->whereNotIn('id', $keptIds)
                ->delete();
        }

        return $summary;
    }

    private function findRecipient(YudisiumPeriod $period, InvitationCategory $category, string $name, ?string $identifier): ?InvitationRecipient
    {
        $query = InvitationRecipient::query()
            ->where('period_id', $period->id)
            ->where('category_id', $category->id);

        if ($identifier) {
            $recipient = (clone $query)->where('identifier', $identifier)->first();

            if ($recipient) {
                return $recipient;
            }
        }

        return $query->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
    }

    /**
     * @param  array<string, array<int, array<string, ?string>>>  $imports
     */
    private function removeStaleLegacyRecipients(YudisiumPeriod $period, array $imports): int
    {
        if ($this->option('keep-missing')) {
            return 0;
        }

        $sourceNameKeys = collect($imports)
            ->flatten(1)
            ->pluck('name')
            ->filter()
            ->map(fn (string $name): string => Str::lower($this->clean($name)))
            ->unique()
            ->values();

        if ($sourceNameKeys->isEmpty()) {
            return 0;
        }

        $legacyCategoryIds = InvitationCategory::query()
            ->where('period_id', $period->id)
            ->whereIn('slug', ['ketuasenat', 'anggota-senat-fakultas-teknik'])
            ->pluck('id');

        if ($legacyCategoryIds->isEmpty()) {
            return 0;
        }

        return InvitationRecipient::query()
            ->where('period_id', $period->id)
            ->whereIn('category_id', $legacyCategoryIds)
            ->whereIn(DB::raw('LOWER(name)'), $sourceNameKeys->all())
            ->delete();
    }

    private function cell(array $row, int $index): string
    {
        return (string) ($row[$index] ?? '');
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }

    private function cleanIdentifier(?string $value): ?string
    {
        $identifier = preg_replace('/\D+/', '', (string) $value);

        return $identifier !== '' ? $identifier : null;
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function joinContext(array $parts): ?string
    {
        $context = collect($parts)
            ->map(fn (string $part): string => $this->clean($part))
            ->filter()
            ->unique()
            ->implode(' - ');

        return $context !== '' ? $context : null;
    }

    private function rowHasValue(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
