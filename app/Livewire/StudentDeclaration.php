<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Declaration;
use App\Support\DeclarationStorage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Upload / view / remove one student's signed parental declaration. Embedded in the
 * student edit drawer (only when the student exists). The file is saved automatically
 * when picked (the updatedFile() hook) — stored encrypted on the private disk via
 * App\Support\DeclarationStorage; one declaration per student (replace on re-upload).
 */
final class StudentDeclaration extends Component
{
    use WithFileUploads;

    public int $studentId;

    /** Temporary Livewire upload; saved automatically in updatedFile(). */
    public $file;

    public function mount(int $studentId): void
    {
        $this->studentId = $studentId;
    }

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return [
            'file.mimes' => 'Dozvoljeno: PDF, JPG ili PNG',
            'file.max' => 'Datoteka je prevelika (najviše 10 MB)',
        ];
    }

    /** Fired after the file finishes uploading to temp storage — validate + store. */
    public function updatedFile(): void
    {
        $this->validate();

        $meta = DeclarationStorage::store($this->file);

        $existing = Declaration::where('student_id', $this->studentId)->first();
        if ($existing !== null) {
            DeclarationStorage::delete($existing->path); // drop the old encrypted file
            $existing->update($meta);
        } else {
            Declaration::create(['student_id' => $this->studentId, ...$meta]);
        }

        $this->reset('file');
        $this->dispatch('students-changed');
        $this->dispatch('toast', message: 'Izjava spremljena');
    }

    public function remove(): void
    {
        $declaration = Declaration::where('student_id', $this->studentId)->first();

        if ($declaration !== null) {
            DeclarationStorage::delete($declaration->path);
            $declaration->delete();
        }

        $this->reset('file');
        $this->dispatch('students-changed');
        $this->dispatch('toast', message: 'Izjava uklonjena');
    }

    public function render()
    {
        return view('livewire.student-declaration', [
            'declaration' => Declaration::where('student_id', $this->studentId)->first(),
        ]);
    }
}
