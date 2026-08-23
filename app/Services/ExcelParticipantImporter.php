<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class ExcelParticipantImporter
{
    public function read(string $path, ?string $originalName = null): array
    {
        $sourceName = $originalName ?: $path;
        $extension = strtolower(pathinfo($sourceName, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->readCsv($path),
            'xlsx' => $this->readXlsx($path),
            default => throw new RuntimeException('Format file tidak didukung. Gunakan template Excel .xlsx.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw new RuntimeException('File CSV tidak bisa dibuka.');
        }

        $rows = [];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rows[] = array_map([$this, 'normalizeCellValue'], $row);
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak bisa dibuka.');
        }

        $sharedStrings = $this->loadSharedStrings($zip);
        $sheetTarget = $this->resolveFirstSheetTarget($zip);
        $sheetXml = $zip->getFromName($sheetTarget);

        if ($sheetXml === false) {
            throw new RuntimeException('Sheet pertama tidak ditemukan pada file XLSX.');
        }

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

            if (! $this->rowIsEmpty($rowValues)) {
                ksort($rowValues);
                $maxColumn = max(array_keys($rowValues));
                $rows[] = array_map(
                    fn ($index) => $rowValues[$index] ?? '',
                    range(0, $maxColumn)
                );
            }
        }

        $zip->close();

        return $rows;
    }

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

            $strings[] = $this->normalizeCellValue($text);
        }

        return $strings;
    }

    private function resolveFirstSheetTarget(ZipArchive $zip): string
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

        $sheetId = $workbookXpath->evaluate('string(//main:sheets/main:sheet[1]/@rel:id)');
        if ($sheetId === '') {
            throw new RuntimeException('Sheet pertama tidak dapat dibaca.');
        }

        $relsDom = new DOMDocument;
        $relsDom->loadXML($relsXml);
        $relsXpath = new DOMXPath($relsDom);
        $relsXpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $target = $relsXpath->evaluate("string(//rel:Relationship[@Id='{$sheetId}']/@Target)");
        if ($target === '') {
            throw new RuntimeException('Target sheet tidak ditemukan.');
        }

        return 'xl/'.ltrim($target, '/');
    }

    private function readCellValue(DOMXPath $xpath, \DOMNode $cellNode, string $type, array $sharedStrings): string
    {
        if ($type === 'inlineStr') {
            $text = '';
            foreach ($xpath->query('.//main:t', $cellNode) as $textNode) {
                $text .= $textNode->textContent;
            }

            return $this->normalizeCellValue($text);
        }

        $valueNode = $xpath->query('./main:v', $cellNode)->item(0);
        if (! $valueNode) {
            return '';
        }

        $value = (string) $valueNode->textContent;

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $this->normalizeCellValue($value);
    }

    private function columnIndexFromCellRef(string $reference): int
    {
        if ($reference === '') {
            return 0;
        }

        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');

        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
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

    private function normalizeCellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }
}
