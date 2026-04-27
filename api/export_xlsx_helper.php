<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Loads Composer autoloader for spreadsheet exports.
 */
function pms_require_spreadsheet_autoload(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new RuntimeException('Export dependency missing: vendor/autoload.php not found.');
    }

    require_once $autoload;
    $loaded = true;
}

/**
 * Clears existing output buffers to prevent corrupted XLSX bytes.
 */
function pms_clear_output_buffers(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

/**
 * Streams a valid XLSX file to browser.
 *
 * @param string $filename
 * @param string $sheetTitle
 * @param array<int,array<int|string,mixed>> $rows
 * @param array<int,int> $headerRows 1-based row indexes that should be styled as table headers.
 */
function pms_stream_xlsx(string $filename, string $sheetTitle, array $rows, array $headerRows = []): void
{
    pms_require_spreadsheet_autoload();

    if (headers_sent()) {
        throw new RuntimeException('Headers already sent before XLSX export.');
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle(substr($sheetTitle, 0, 31));

    $maxColumns = 0;
    foreach ($rows as $rowIndex => $rowData) {
        $values = array_values($rowData);
        $columnCount = count($values);
        $maxColumns = max($maxColumns, $columnCount);

        foreach ($values as $colIndex => $value) {
            $cellCoordinate = Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1);
            $sheet->setCellValue($cellCoordinate, $value);
        }
    }

    foreach ($headerRows as $headerRow) {
        if ($headerRow < 1 || $maxColumns < 1) {
            continue;
        }

        $endColumn = Coordinate::stringFromColumnIndex($maxColumns);
        $range = 'A' . $headerRow . ':' . $endColumn . $headerRow;
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDE9FE');
    }

    if ($maxColumns > 0) {
        for ($i = 1; $i <= $maxColumns; $i++) {
            $column = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    pms_clear_output_buffers();

    $safeFile = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $filename) ?: 'export.xlsx';
    if (strtolower(substr($safeFile, -5)) !== '.xlsx') {
        $safeFile .= '.xlsx';
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeFile . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    $spreadsheet->disconnectWorksheets();

    exit;
}
