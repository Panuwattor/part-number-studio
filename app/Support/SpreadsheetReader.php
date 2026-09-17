<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads the first sheet of an uploaded spreadsheet into plain rows.
 *
 * Part numbers arrive from all sorts of exports, so the reader is deliberately
 * forgiving: it ignores formatting, keeps every column as a trimmed string, and
 * leaves the question of which column holds the code to the caller.
 */
class SpreadsheetReader
{
    /** Never read an unbounded sheet into memory. */
    public const MAX_ROWS = 5000;

    public const MAX_COLS = 30;

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>, truncated: bool, sheet: string}
     */
    public static function read(string $path, ?string $extension = null): array
    {
        $reader = $extension
            ? IOFactory::createReader(self::readerName($extension))
            : IOFactory::createReaderForFile($path);

        // Formatting and formulas are irrelevant here; values only keeps it fast
        $reader->setReadDataOnly(true);

        $sheet = $reader->load($path)->getSheet(0);

        $lastRow = min($sheet->getHighestDataRow(), self::MAX_ROWS + 1);
        $lastCol = min(
            Coordinate::columnIndexFromString($sheet->getHighestDataColumn()),
            self::MAX_COLS
        );

        $rows = [];

        for ($r = 1; $r <= $lastRow; $r++) {
            $line = [];
            $hasValue = false;

            for ($c = 1; $c <= $lastCol; $c++) {
                $cell = $sheet->getCell([$c, $r]);
                $value = $cell->getValue();

                // A date-formatted cell would otherwise read as a serial number
                if ($value !== null && $value !== '' && ExcelDate::isDateTime($cell)) {
                    $value = ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
                }

                $value = trim((string) ($value ?? ''));
                $line[] = $value;

                if ($value !== '') {
                    $hasValue = true;
                }
            }

            // Blank rows are noise from the export, not data
            if ($hasValue) {
                $rows[] = $line;
            }
        }

        // The first row is offered as headers, but the caller decides whether
        // to treat it as one — plenty of exports start straight at the data.
        $headers = $rows ? $rows[0] : [];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'truncated' => $sheet->getHighestDataRow() > self::MAX_ROWS,
            'sheet' => $sheet->getTitle(),
        ];
    }

    private static function readerName(string $extension): string
    {
        return match (strtolower($extension)) {
            'xlsx', 'xlsm' => 'Xlsx',
            'xls' => 'Xls',
            'csv', 'txt' => 'Csv',
            'ods' => 'Ods',
            default => 'Xlsx',
        };
    }

    /**
     * A column label for the picker: the header text when there is one,
     * otherwise the spreadsheet letter.
     */
    public static function columnLabel(int $index, array $headers, bool $firstRowIsHeader): string
    {
        $letter = Coordinate::stringFromColumnIndex($index + 1);

        if ($firstRowIsHeader && ($headers[$index] ?? '') !== '') {
            return $letter.' — '.$headers[$index];
        }

        return 'Column '.$letter;
    }
}
