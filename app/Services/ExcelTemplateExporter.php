<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class ExcelTemplateExporter
{
    public function participantTemplate(iterable $studyPrograms = []): string
    {
        return $this->buildWorkbook(
            'Template Mahasiswa',
            ['no_urut', 'kode_prodi', 'nim', 'nama', 'tanggal_lahir', 'fakultas'],
            [5]
        );
    }

    public function privateRecipientTemplate(string $categoryTitle): string
    {
        return $this->buildWorkbook(
            'Template '.$categoryTitle,
            ['sapaan', 'nama', 'catatan']
        );
    }

    private function buildWorkbook(string $title, array $headers, array $dateColumns = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'undangan-template-');
        if ($path === false) {
            throw new RuntimeException('Template Excel tidak bisa dibuat.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Template Excel tidak bisa dibuka untuk ditulis.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->appXml());
        $zip->addFromString('docProps/core.xml', $this->coreXml($title));
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml([$headers], true, $dateColumns));
        $zip->close();

        return $path;
    }

    private function sheetXml(array $rows, bool $freezeHeader, array $dateColumns = []): string
    {
        $maxColumns = collect($rows)->map(fn ($row) => count($row))->max() ?: 1;
        $columns = collect(range(1, $maxColumns))
            ->map(function ($index) use ($dateColumns) {
                $width = in_array($index, $dateColumns, true) ? 18 : ($index === 1 ? 24 : 32);
                $style = in_array($index, $dateColumns, true) ? ' style="2"' : '';

                return '<col min="'.$index.'" max="'.$index.'" width="'.$width.'" customWidth="1"'.$style.'/>';
            })
            ->implode('');

        $sheetRows = collect($rows)
            ->map(function (array $row, int $rowIndex) {
                $rowNumber = $rowIndex + 1;
                $cells = collect($row)
                    ->map(fn ($value, $columnIndex) => $this->cellXml($columnIndex + 1, $rowNumber, (string) $value, $rowNumber === 1))
                    ->implode('');

                return '<row r="'.$rowNumber.'">'.$cells.'</row>';
            })
            ->implode('');

        $freeze = $freezeHeader
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$freeze
            .'<cols>'.$columns.'</cols>'
            .'<sheetData>'.$sheetRows.'</sheetData>'
            .$this->dataValidationsXml($dateColumns)
            .'</worksheet>';
    }

    private function dataValidationsXml(array $dateColumns): string
    {
        if ($dateColumns === []) {
            return '';
        }

        $validations = collect($dateColumns)
            ->map(function (int $column) {
                $range = $this->columnName($column).'2:'.$this->columnName($column).'5000';

                return '<dataValidation type="date" operator="between" allowBlank="1" showInputMessage="1" showErrorMessage="1" sqref="'.$range.'" promptTitle="Tanggal Lahir" prompt="Gunakan format tanggal-bulan-tahun, contoh 12-10-2007." errorTitle="Tanggal tidak valid" error="Gunakan format tanggal-bulan-tahun, contoh 12-10-2007."><formula1>DATE(1900,1,1)</formula1><formula2>DATE(2100,12,31)</formula2></dataValidation>';
            })
            ->implode('');

        return '<dataValidations count="'.count($dateColumns).'">'.$validations.'</dataValidations>';
    }

    private function cellXml(int $column, int $row, string $value, bool $header): string
    {
        $reference = $this->columnName($column).$row;
        $style = $header ? ' s="1"' : '';

        return '<c r="'.$reference.'" t="inlineStr"'.$style.'><is><t>'.$this->escape($value).'</t></is></c>';
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Data" sheetId="1" r:id="rId1"/>'
            .'</sheets>'
            .'</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="dd-mm-yyyy"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAD3"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            .'</styleSheet>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Laravel</Application>'
            .'</Properties>';
    }

    private function coreXml(string $title): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$this->escape($title).'</dc:title>'
            .'<dc:creator>Undangan Yudisium</dc:creator>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.now()->toIso8601String().'</dcterms:created>'
            .'</cp:coreProperties>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
