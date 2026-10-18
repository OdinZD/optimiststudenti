<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

use App\Models\EracuniApiLog;
use Throwable;

/**
 * Wraps an eRačuni call: times it and writes one append-only log row with the request
 * payload STRIPPED of credentials. Both the mock and the future HTTP client go through
 * here so redaction lives in exactly one place. Failures are logged (ok=false) and rethrown.
 */
final class EracuniApiLogger
{
    /** Keys never persisted to the log, whichever envelope shape is used. */
    private const SECRET_KEYS = ['username', 'secretKey', 'secret_key', 'token', 'md5pass', 'password'];

    /**
     * @template T
     *
     * @param  array<string, mixed>  $request  full request (creds included — they are redacted here)
     * @param  callable(): T  $call
     * @return T
     */
    public static function record(string $method, array $request, callable $call): mixed
    {
        $started = microtime(true);

        try {
            $result = $call();
            self::write($method, $request, true, null, $result, $started);

            return $result;
        } catch (Throwable $e) {
            self::write($method, $request, false, $e->getMessage(), null, $started);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $request
     */
    private static function write(string $method, array $request, bool $ok, ?string $error, mixed $result, float $started): void
    {
        EracuniApiLog::create([
            'method' => $method,
            'ok' => $ok,
            'request' => self::redact($request),
            'response' => $result !== null ? mb_substr((string) json_encode($result, JSON_UNESCAPED_UNICODE), 0, 2000) : null,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    private static function redact(array $request): array
    {
        return array_diff_key($request, array_flip(self::SECRET_KEYS));
    }
}
