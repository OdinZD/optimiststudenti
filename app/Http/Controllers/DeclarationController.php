<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Student;
use App\Support\DeclarationStorage;
use Illuminate\Http\Response;

/**
 * Streams a child's signed declaration, decrypted, to a logged-in trainer.
 * Reached only via the auth-protected /izjava/{student} route — the file on disk is
 * encrypted and outside the web root, so there is no direct URL to it.
 */
final class DeclarationController
{
    public function __invoke(Student $student): Response
    {
        $declaration = $student->declaration;

        abort_if($declaration === null, 404);

        return response(DeclarationStorage::get($declaration->path), 200, [
            'Content-Type' => $declaration->mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($declaration->original_name).'"',
        ]);
    }
}
