<?php

namespace App\Support;

use ZipArchive;

/** A one-sheet .xlsx file (header row + rows of text and numbers), for report exports. */
class XlsxWriter
{
    public static function build(array $headers, array $rows, string $sheetName = 'Report'): string
    {
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $i): string {
            $name = '';
            for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
                $name = chr(65 + ($i - 1) % 26) . $name;
            }

            return $name;
        };

        $xmlRows = '';
        foreach (array_merge([$headers], $rows) as $r => $row) {
            $cells = '';
            foreach (array_values($row) as $c => $value) {
                $ref = $col($c) . ($r + 1);
                $cells .= is_int($value) || is_float($value)
                    ? '<c r="' . $ref . '"><v>' . $value . '</v></c>'
                    : '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $esc($value) . '</t></is></c>';
            }
            $xmlRows .= '<row r="' . ($r + 1) . '">' . $cells . '</row>';
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $esc(mb_substr($sheetName, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $xmlRows . '</sheetData></worksheet>');
        $zip->close();

        $bytes = file_get_contents($path);
        @unlink($path);

        return $bytes;
    }

    /** Back from build(): the rows as arrays (for checking an export). */
    public static function read(string $bytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $bytes);
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        @unlink($path);

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $cells[] = isset($c->is) ? (string) $c->is->t : (string) $c->v;
            }
            $rows[] = $cells;
        }

        return $rows;
    }
}
