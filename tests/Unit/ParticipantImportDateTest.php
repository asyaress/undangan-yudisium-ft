<?php

namespace Tests\Unit;

use App\Http\Controllers\ParticipantImportController;
use App\Services\ExcelTemplateExporter;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ZipArchive;

class ParticipantImportDateTest extends TestCase
{
    public function test_compact_birth_date_number_is_read_as_day_month_year(): void
    {
        $this->assertSame('2007-10-12', $this->parseBirthDate('12102007'));
        $this->assertSame('2007-02-01', $this->parseBirthDate('1022007'));
    }

    public function test_excel_date_serial_is_still_supported(): void
    {
        $this->assertSame('2007-10-12', $this->parseBirthDate('39367'));
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
        $records = $this->participantRecordsFromRows([
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
        $this->assertSame('3', $records[2]['sequence_number']);
        $this->assertSame('2109056027', $records[0]['nim']);
        $this->assertSame('Teknik Pertambangan', $records[0]['study_program']);
        $this->assertSame('Teknik Elektro', $records[2]['study_program']);
        $this->assertNull($records[0]['birth_date']);
    }

    private function parseBirthDate(string $value): ?string
    {
        $controller = new ParticipantImportController;
        $method = new ReflectionMethod($controller, 'parseBirthDate');
        $method->setAccessible(true);

        return $method->invoke($controller, $value);
    }

    private function participantRecordsFromRows(array $rows): array
    {
        $controller = new ParticipantImportController;
        $method = new ReflectionMethod($controller, 'participantRecordsFromRows');
        $method->setAccessible(true);

        return $method->invoke($controller, $rows);
    }
}
