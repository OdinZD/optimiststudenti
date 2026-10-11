<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\SpreadsheetWriter;
use OpenSpout\Reader\XLSX\Reader;
use PHPUnit\Framework\TestCase;

final class SpreadsheetWriterTest extends TestCase
{
    private string $path = '';

    protected function tearDown(): void
    {
        if ($this->path !== '' && is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_writes_banner_header_and_data_rows_in_order(): void
    {
        $this->path = sys_get_temp_dir().'/ssw-'.uniqid().'.xlsx';

        $banner = 'Karate klub Optimist — Popis polaznika · 11.10.2026.';
        $export = [
            'headers' => ['Prezime', 'Ime', 'Telefon'],
            'rows' => [
                ['Anđić', 'Laura', '091 234 5678'],
                ['Babić', 'Marko', '098 111 2222'],
                // long value exercises the clamped content-fit width without corrupting data
                ['Cvić', 'VrloDugačkoImePrezimeZaProvjeruŠirineStupca', '—'],
            ],
        ];

        SpreadsheetWriter::write($this->path, $banner, $export);

        $rows = $this->readRows($this->path);

        $this->assertCount(5, $rows); // banner + header + 3 data
        $this->assertSame($banner, $rows[0][0]);
        $this->assertSame(['Prezime', 'Ime', 'Telefon'], $rows[1]);
        $this->assertSame(['Anđić', 'Laura', '091 234 5678'], $rows[2]);
        $this->assertSame(['Babić', 'Marko', '098 111 2222'], $rows[3]);
        $this->assertSame(['Cvić', 'VrloDugačkoImePrezimeZaProvjeruŠirineStupca', '—'], $rows[4]);
    }

    public function test_write_sections_lays_out_banner_titles_headers_and_members(): void
    {
        $this->path = sys_get_temp_dir().'/ssw-sec-'.uniqid().'.xlsx';

        $banner = 'Karate klub Optimist — Kata timovi · 11.10.2026.';
        $headers = ['Prezime', 'Ime', 'Datum rođenja', 'Uzrasna kategorija'];
        $sections = [
            ['title' => 'Tim A', 'color' => '#2D3E7B', 'rows' => [
                ['Anđić', 'Laura', '15.05.2014.', 'U12 · Učenici'],
                ['Zubak', 'Ana', '01.03.2014.', 'U12 · Učenici'],
                ['Čović', 'Marko', '02.02.2014.', 'U12 · Učenici'],
            ]],
            ['title' => 'Tim B', 'color' => '#C23B30', 'rows' => [
                ['Horvat', 'Ivo', '01.01.2015.', 'U10 · Mlađi učenici'],
                ['Kovač', 'Iva', '02.02.2015.', 'U10 · Mlađi učenici'],
                ['Novak', 'Ena', '03.03.2015.', 'U10 · Mlađi učenici'],
            ]],
        ];

        SpreadsheetWriter::writeSections($this->path, $banner, $headers, $sections);

        $rows = $this->readRows($this->path);
        $firstCells = array_map(static fn (array $r): string => (string) ($r[0] ?? ''), $rows);

        // Empty spacer rows are skipped by the reader, so the first four rows are deterministic.
        $this->assertSame($banner, $rows[0][0]);
        $this->assertSame('Tim A', $rows[1][0]);                                         // team-title row
        $this->assertSame($headers, $rows[2]);                                           // header repeated per team
        $this->assertSame(['Anđić', 'Laura', '15.05.2014.', 'U12 · Učenici'], $rows[3]); // first member
        $this->assertContains('Tim B', $firstCells);                                     // second team block present
        $this->assertContains('Horvat', $firstCells);
    }

    /**
     * @return list<list<mixed>>
     */
    private function readRows(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }

        $reader->close();

        return $rows;
    }
}
