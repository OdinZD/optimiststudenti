<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\StudentDelete;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class StudentDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_confirm_event_opens_dialog_with_name(): void
    {
        $student = Student::create(['last_name' => 'Kardum', 'first_name' => 'Karmen']);

        Livewire::test(StudentDelete::class)
            ->call('confirm', $student->id)
            ->assertSet('confirming', true)
            ->assertSet('studentName', 'Kardum Karmen');
    }

    public function test_delete_soft_deletes_and_offers_undo(): void
    {
        $student = Student::create(['last_name' => 'Kardum', 'first_name' => 'Karmen']);

        Livewire::test(StudentDelete::class)
            ->call('confirm', $student->id)
            ->call('delete')
            ->assertSet('confirming', false)
            ->assertSet('showUndo', true)
            ->assertSet('deletedName', 'Kardum Karmen')
            ->assertDispatched('students-changed')
            ->assertDispatched('student-deleted');

        $this->assertSoftDeleted($student);
    }

    public function test_undo_restores_the_student(): void
    {
        $student = Student::create(['last_name' => 'Kardum', 'first_name' => 'Karmen']);

        Livewire::test(StudentDelete::class)
            ->call('confirm', $student->id)
            ->call('delete')
            ->call('undo')
            ->assertSet('showUndo', false)
            ->assertDispatched('students-changed');

        $this->assertNotSoftDeleted($student);
        $this->assertSame(1, Student::count());
    }
}
