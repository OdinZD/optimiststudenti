<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

use RuntimeException;

/** Thrown when an eRačuni API call fails (API error, transport error, bad response). */
final class EracuniException extends RuntimeException {}
