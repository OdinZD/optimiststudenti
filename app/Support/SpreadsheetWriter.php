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
 * Writes an export to a styled .xlsx. Two layouts:
 *  - write(): a merged title banner, one bold indigo header, zebra data rows.
 *  - writeSections(): a banner, then one coloured block per section (team) — a merged
 *    team-title row, the column header, then that section's zebra rows + a spacer.
 * Colours match the app/PDF (indigo #2D3E7B). Styling uses OpenSpout v5.3's Style API.
 */
final class SpreadsheetWriter
{
    private const string INDIGO = '2D3E7B';

    private const string ZEBRA = 'EAF0FF';

    private const string GRID = 'D9DCE3';

    private const string INK = '14171F';

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
        self::applyColumnWidths($options, $headers, [$rows]);
        if ($columnCount > 0) {
            // Banner spans all columns on row 1 (mergeCells columns are 0-based here).
            $options->mergeCells(0, 1, $columnCount - 1, 1);
        }

        [$bannerStyle, $headerStyle, $evenStyle, $oddStyle] = self::coreStyles();

        $writer = new Writer($options);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValuesWithStyle([$banner], $bannerStyle));
        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        foreach ($rows as $i => $row) {
            $writer->addRow(Row::fromValuesWithStyle($row, $i % 2 === 0 ? $evenStyle : $oddStyle));
        }

        $writer->close();
    }

    /**
     * One coloured block per section (e.g. a kata team) under a shared banner.
     *
     * @param  list<string>  $headers
     * @param  list<array{title: string, color: string, rows: list<list<string>>}>  $sections
     */
    public static function writeSections(string $path, string $banner, array $headers, array $sections): void
    {
        $lastColumn = max(0, count($headers) - 1);

        $options = new Options;
        self::applyColumnWidths($options, $headers, array_column($sections, 'rows'));

        // Merges are pre-computed from the fixed row layout: banner (row 1) + each team-title row.
        $options->mergeCells(0, 1, $lastColumn, 1);
        $row = 2;
        foreach ($sections as $section) {
            $options->mergeCells(0, $row, $lastColumn, $row);  // team-title row
            $row += 2 + count($section['rows']) + 1;           // title + header + members + spacer
        }

        [$bannerStyle, $headerStyle, $evenStyle, $oddStyle] = self::coreStyles();

        $writer = new Writer($options);
        $writer->openToFile($path);

        $writer->addRow(Row::fromValuesWithStyle([$banner], $bannerStyle));

        foreach ($sections as $section) {
            $writer->addRow(Row::fromValuesWithStyle([$section['title']], self::titleStyle($section['color'])));
            $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

            foreach ($section['rows'] as $i => $memberRow) {
                $writer->addRow(Row::fromValuesWithStyle($memberRow, $i % 2 === 0 ? $evenStyle : $oddStyle));
            }

            $writer->addRow(Row::fromValues([''])); // blank spacer between teams
        }

        $writer->close();
    }

    /**
     * @return array{0: Style, 1: Style, 2: Style, 3: Style} banner, header, even, odd
     */
    private static function coreStyles(): array
    {
        $border = self::thinBorder();

        return [
            (new Style)->withFontBold(true)->withFontSize(13)->withFontColor(self::INDIGO)->withCellAlignment(CellAlignment::CENTER),
            (new Style)->withFontBold(true)->withFontSize(11)->withFontColor(Color::WHITE)->withBackgroundColor(self::INDIGO)->withCellAlignment(CellAlignment::CENTER)->withBorder($border),
            (new Style)->withFontSize(11)->withBackgroundColor(Color::WHITE)->withBorder($border),
            (new Style)->withFontSize(11)->withBackgroundColor(self::ZEBRA)->withBorder($border),
        ];
    }

    /** Team-title row: bold, filled with the team colour; text colour chosen by luminance. */
    private static function titleStyle(string $color): Style
    {
        $hex = ltrim($color, '#');

        return (new Style)
            ->withFontBold(true)
            ->withFontSize(12)
            ->withFontColor(self::isLight($hex) ? self::INK : Color::WHITE)
            ->withBackgroundColor($hex);
    }

    private static function isLight(string $hex): bool
    {
        if (strlen($hex) !== 6) {
            return false;
        }

        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        // Perceived luminance (0–255); above ~150 reads better with dark text.
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150;
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
     * Fit each column to its widest value across the header and every row group.
     *
     * @param  list<string>  $headers
     * @param  list<list<list<string>>>  $rowGroups  one or more groups of rows
     */
    private static function applyColumnWidths(Options $options, array $headers, array $rowGroups): void
    {
        foreach ($headers as $c => $header) {
            $max = mb_strlen($header);
            foreach ($rowGroups as $rows) {
                foreach ($rows as $row) {
                    $max = max($max, mb_strlen((string) ($row[$c] ?? '')));
                }
            }

            $width = min(self::MAX_WIDTH, max(self::MIN_WIDTH, $max + 2));
            // setColumnWidth columns are 1-based (A = 1), unlike mergeCells above.
            $options->setColumnWidth($width, $c + 1);
        }
    }
}
