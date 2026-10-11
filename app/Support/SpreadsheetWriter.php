<?php

declare(strict_types=1);

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes an {headers, rows} export to a styled .xlsx: a merged title banner,
 * a bold indigo header, zebra-striped data rows, thin cell borders and
 * content-fit column widths. Colours match the app/PDF (indigo #2D3E7B).
 */
final class SpreadsheetWriter
{
    private const string INDIGO = '2D3E7B';

    private const string ZEBRA = 'EAF0FF';

    private const string GRID = 'D9DCE3';

    private const float MIN_WIDTH = 10.0;

    private const float MAX_WIDTH = 45.0;

    /**
     * @param  array{headers: list<string>, rows: list<list<string>>}  $export
     */
    public static function write(string $path, string $banner, array $export): void
    {
        $headers = $export['headers'];
        $rows = $export['rows'];
        $columnCount = count($headers);

        $options = new Options;
        self::applyColumnWidths($options, $headers, $rows);
        if ($columnCount > 0) {
            // Banner spans all columns on row 1 (mergeCells columns are 0-based here).
            $options->mergeCells(0, 1, $columnCount - 1, 1);
        }

        $border = self::thinBorder();
        $bannerStyle = (new Style)
            ->withFontBold(true)
            ->withFontSize(13)
            ->withFontColor(self::INDIGO)
            ->withCellAlignment(CellAlignment::CENTER);
        $headerStyle = (new Style)
            ->withFontBold(true)
            ->withFontSize(11)
            ->withFontColor(Color::WHITE)
            ->withBackgroundColor(self::INDIGO)
            ->withCellAlignment(CellAlignment::CENTER)
            ->withBorder($border);
        $evenStyle = (new Style)->withFontSize(11)->withBackgroundColor(Color::WHITE)->withBorder($border);
        $oddStyle = (new Style)->withFontSize(11)->withBackgroundColor(self::ZEBRA)->withBorder($border);

        $writer = new Writer($options);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValuesWithStyle([$banner], $bannerStyle));
        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        foreach ($rows as $i => $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $i % 2 === 0 ? $evenStyle : $oddStyle));
        }

        $writer->close();
    }

    private static function thinBorder(): Border
    {
        return new Border(
            new BorderPart(BorderName::TOP, self::GRID, BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::BOTTOM, self::GRID, BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::LEFT, self::GRID, BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::RIGHT, self::GRID, BorderWidth::THIN, BorderStyle::SOLID),
        );
    }

    /**
     * Fit each column to its widest value (header or any cell); the banner is
     * excluded since it is merged across all columns.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private static function applyColumnWidths(Options $options, array $headers, array $rows): void
    {
        foreach ($headers as $c => $header) {
            $max = mb_strlen($header);
            foreach ($rows as $row) {
                $max = max($max, mb_strlen((string) ($row[$c] ?? '')));
            }

            $width = min(self::MAX_WIDTH, max(self::MIN_WIDTH, $max + 2));
            // setColumnWidth columns are 1-based (A = 1), unlike mergeCells above.
            $options->setColumnWidth($width, $c + 1);
        }
    }
}
