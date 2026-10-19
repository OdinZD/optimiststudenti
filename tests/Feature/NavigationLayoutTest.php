<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_and_mobile_menu_are_rendered_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/polaznici')
            ->assertOk()
            ->assertSee('Glavna navigacija')
            ->assertSee('Polaznici')
            ->assertSee('Natjecanja')
            ->assertSee('Kata timovi')
            ->assertSee('Odjava')
            ->assertSee('aria-current="page"', false)
            ->assertSee('id="mobilni-izbornik"', false)
            ->assertSee('aria-label="Izbornik"', false);
    }
}
