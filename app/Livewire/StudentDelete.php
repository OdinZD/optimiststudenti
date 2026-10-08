<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Student;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Soft-delete confirmation dialog + "Poništi" (undo) toast (SPEC §4.7, §5.5–5.6).
 */
final class StudentDelete extends Component
{
    public bool $confirming = false;

    public ?int $studentId = null;

    public string $studentName = '';

    public bool $showUndo = false;

    public ?int $deletedId = null;

    public string $deletedName = '';

    #[On('confirm-delete-student')]
    public function confirm(int $id): void
    {
        $student = Student::find($id);

        if ($student === null) {
            return;
        }

        $this->studentId = $student->id;
        $this->studentName = "{$student->last_name} {$student->first_name}";
        $this->confirming = true;
    }

    public function cancel(): void
    {
        $this->confirming = false;
    }

    public function delete(): void
    {
        $student = $this->studentId !== null ? Student::find($this->studentId) : null;

        $this->confirming = false;

        if ($student === null) {
            return;
        }

        $this->deletedId = $student->id;
        $this->deletedName = "{$student->last_name} {$student->first_name}";
        $student->delete();

        $this->showUndo = true;

        $this->dispatch('students-changed');
        $this->dispatch('student-deleted'); // closes the edit drawer if open
    }

    public function undo(): void
    {
        if ($this->deletedId !== null) {
            Student::withTrashed()->find($this->deletedId)?->restore();
        }

        $this->dismissUndo();
        $this->dispatch('students-changed');
    }

    public function dismissUndo(): void
    {
        $this->showUndo = false;
        $this->deletedId = null;
    }

    public function render()
    {
        return view('livewire.student-delete');
    }
}
