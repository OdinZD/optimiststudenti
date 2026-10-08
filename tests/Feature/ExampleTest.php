<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_root_redirects_to_students_list(): void
    {
        $this->get('/')->assertRedirect('/polaznici');
    }
}
