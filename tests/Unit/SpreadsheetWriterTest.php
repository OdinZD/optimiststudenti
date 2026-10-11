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
