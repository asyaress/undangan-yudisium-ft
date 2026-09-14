<?php

namespace Tests\Unit;

use App\Services\ExcelParticipantImporter;
use App\Services\ExcelTemplateExporter;
use App\Services\ParticipantRosterMapper;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ParticipantImportDateTest extends TestCase
{
    public function test_compact_birth_date_number_is_read_as_day_month_year(): void
    {
        $mapper = new ParticipantRosterMapper;

        $this->assertSame('2007-10-12', $mapper->parseBirthDate('12102007'));
        $this->assertSame('2007-02-01', $mapper->parseBirthDate('1022007'));
    }

    public function test_excel_date_serial_is_still_supported(): void
    {
        $this->assertSame('2007-10-12', (new ParticipantRosterMapper)->parseBirthDate('39367'));
    }

    public function test_participant_template_formats_birth_date_column_as_date(): void
    {
        $exporter = new ExcelTemplateExporter;
        $path = $exporter->participantTemplate([]);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $stylesXml = $zip->getFromName('xl/styles.xml');

        $zip->close();
        @unlink($path);

        $this->assertIsString($sheetXml);
        $this->assertIsString($stylesXml);
        $this->assertStringContainsString('<col min="5" max="5" width="18" customWidth="1" style="2"/>', $sheetXml);
        $this->assertStringContainsString('type="date"', $sheetXml);
        $this->assertStringContainsString('sqref="E2:E5000"', $sheetXml);
        $this->assertStringContainsString('formatCode="dd-mm-yyyy"', $stylesXml);
    }

    public function test_attendance_block_rows_are_mapped_in_file_order(): void
    {
        $records = (new ParticipantRosterMapper)->recordsFromRows([
            ['DAFTAR HADIR YUDISIAWAN/I'],
            ['TEKNIK PERTAMBANGAN'],
            ['NO', '', 'NAMA', '', '', 'PROGRAM STUDI', 'IPK', '', 'PREDIKAT'],
            ['1', '6905', 'Natasya Amanda Putri', '2109056027', 'P', 'S1 Teknik Pertambangan'],
            ['2', '6906', 'Tari Pramesti Hanifatul Fauziah', '1809055012', 'L', 'S1 Teknik Pertambangan'],
            ['TEKNIK ELEKTRO'],
            ['NO', '', 'NAMA', '', '', 'PROGRAM STUDI', 'IPK', '', 'PREDIKAT'],
            ['1', '7001', 'Mahasiswa Elektro', '2009076001', 'L', 'S1 Teknik Elektro'],
        ]);

        $this->assertCount(3, $records);
        $this->assertSame('1', $records[0]['sequence_number']);
        $this->assertSame('2', $records[1]['sequence_number']);
        $this->assertSame('1', $records[2]['sequence_number']);
        $this->assertSame('2109056027', $records[0]['nim']);
        $this->assertSame('Teknik Pertambangan', $records[0]['study_program']);
        $this->assertSame('Teknik Elektro', $records[2]['study_program']);
        $this->assertNull($records[0]['birth_date']);
    }

    public function test_september_absensi_layout_uses_nim_column_and_skips_headers(): void
    {
        $records = (new ParticipantRosterMapper)->recordsFromRows([
            ['DAFTAR HADIR YUDISIAWAN/I'],
            ['YUDISIUM ANGKATAN 83 PERIODE 3 SEPTEMBER 2026'],
            ['TEKNIK PERTAMBANGAN'],
            ['NO', '', 'NAMA', '', '', 'NIM', 'TANGGAL LAHIR', 'PROGRAM STUDI'],
            ['1', 'Intan Wulandari', 'Intan Wulandari', '2109056027', 'P', '2109056004', '', 'S1 Teknik Pertambangan'],
            ['NO', '', 'NAMA', '', '', 'NIM', 'TANGGAL LAHIR', 'PROGRAM STUDI'],
            ['2', 'Adit Ajie Nugraha', 'Adit Ajie Nugraha', '1809055012', 'L', '2109056036', '', 'S1 Teknik Pertambangan'],
            ['INFORMATIKA'],
            ['41', '', 'Dennis Cristian', '', '', '1915036139', '', 'S1 Sistem Informasi'],
        ]);

        $this->assertCount(3, $records);
        $this->assertSame('2109056004', $records[0]['nim']);
        $this->assertSame('Intan Wulandari', $records[0]['name']);
        $this->assertSame('Teknik Pertambangan', $records[0]['study_program']);
        $this->assertSame('2109056036', $records[1]['nim']);
        $this->assertSame('1915036139', $records[2]['nim']);
        $this->assertSame('Sistem Informasi', $records[2]['study_program']);
    }

    public function test_committed_september_2026_workbook_parses_clean_student_rows(): void
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'imports'.DIRECTORY_SEPARATOR.'absensi-mahasiswa-september-2026.xlsx';
        if (! is_file($path)) {
            $this->markTestSkipped('File absensi deployment belum tersedia.');
        }

        $rows = (new ExcelParticipantImporter)->read($path);
        $records = (new ParticipantRosterMapper)->recordsFromRows($rows);
        $nims = array_column($records, 'nim');

        $this->assertGreaterThanOrEqual(250, count($records));
        $this->assertSame(count($records), count(array_unique($nims)));

        foreach ($records as $record) {
            $this->assertMatchesRegularExpression('/^\d{7,20}$/', (string) $record['nim']);
            $this->assertNotSame('NAMA', strtoupper((string) $record['name']));
            $this->assertNotEmpty($record['study_program']);
        }
    }
}
