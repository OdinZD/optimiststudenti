<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores a child's signed parental declaration on the private disk
 * (storage/app/private/izjave), encrypted at rest with the app key and under a
 * random name — never web-accessible, never in git. Read only via an auth route.
 */
final class DeclarationStorage
{
    private const string DISK = 'local';

    private const string DIR = 'izjave';

    /**
     * @return array{path: string, original_name: string, mime: string, size: int}
     */
    public static function store(UploadedFile $file): array
    {
        $path = self::DIR.'/'.Str::uuid()->toString();
        Storage::disk(self::DISK)->put($path, Crypt::encryptString($file->get()));

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) ($file->getSize() ?? 0),
        ];
    }

    /** Decrypted file contents. */
    public static function get(string $path): string
    {
        return Crypt::decryptString(Storage::disk(self::DISK)->get($path));
    }

    public static function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }
}
