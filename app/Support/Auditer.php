<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes one append-only audit entry (who, when, old → new). Used by the
 * Članarine money operations and reference-data changes.
 */
final class Auditer
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function log(string $action, ?Model $auditable = null, ?array $old = null, ?array $new = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable !== null ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip' => Request::ip(),
        ]);
    }
}
