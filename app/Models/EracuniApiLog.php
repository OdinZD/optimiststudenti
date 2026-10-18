<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One append-only row per eRačuni API call (who/what/outcome). Never stores the
 * credentials — the request payload is redacted by App\Services\Eracuni\EracuniApiLogger.
 */
final class EracuniApiLog extends Model
{
    public const UPDATED_AT = null; // append-only: samo created_at

    protected $table = 'eracuni_api_log';

    protected $fillable = [
        'method',
        'ok',
        'http_status',
        'request',
        'response',
        'error',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
            'request' => 'array',
        ];
    }
}
