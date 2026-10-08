<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Console\Command;

/**
 * Reads the normalized student dataset. The file holds personal data of minors
 * (OIBs, birth dates) and is gitignored, so it may be absent on CI or a fresh
 * clone — callers must handle a null return by skipping gracefully (SPEC §8).
 *
 * @phpstan-type Dataset array{
 *     locations: list<array{slug: string, name: string}>,
 *     training_groups: list<array{slug: string, name: string, description?: ?string}>,
 *     students: list<array<string, mixed>>
 * }
 */
final class PolazniciDataset
{
    public const PATH = 'seeders/data/polaznici.json';

    /** @return Dataset|null */
    public static function read(?Command $command = null): ?array
    {
        $path = database_path(self::PATH);

        if (! is_file($path)) {
            $command?->warn('Dataset not found at database/'.self::PATH.' — skipping import.');

            return null;
        }

        /** @var Dataset $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }
}
