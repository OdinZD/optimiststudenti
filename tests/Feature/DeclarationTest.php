<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\StudentDeclaration;
use App\Livewire\StudentsList;
use App\Models\Declaration;
use App\Models\Student;
use App\Models\User;
use App\Support\DeclarationStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class DeclarationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    public function test_storage_encrypts_the_file_at_rest(): void
    {
        $file = UploadedFile::fake()->createWithContent('izjava.pdf', 'TAJNI SADRŽAJ IZJAVE');

        $meta = DeclarationStorage::store($file);

        // on disk it must be ciphertext, but decrypts back to the original
        $this->assertNotSame('TAJNI SADRŽAJ IZJAVE', Storage::disk('local')->get($meta['path']));
        $this->assertSame('TAJNI SADRŽAJ IZJAVE', DeclarationStorage::get($meta['path']));
        $this->assertStringStartsWith('izjave/', $meta['path']);
    }

    public function test_upload_creates_declaration_and_replace_removes_old_file(): void
    {
        $child = Student::create(['last_name' => 'Anđić', 'first_name' => 'Laura']);

        // picking a file auto-saves it (updatedFile hook)
        Livewire::test(StudentDeclaration::class, ['studentId' => $child->id])
            ->set('file', UploadedFile::fake()->create('prva.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors()
            ->assertDispatched('students-changed');

        $first = Declaration::where('student_id', $child->id)->firstOrFail();
        $this->assertSame('prva.pdf', $first->original_name);
        Storage::disk('local')->assertExists($first->path);

        // re-upload replaces: old encrypted file gone, still exactly one row
        Livewire::test(StudentDeclaration::class, ['studentId' => $child->id])
            ->set('file', UploadedFile::fake()->create('druga.pdf', 50, 'application/pdf'))
            ->assertHasNoErrors();

        $this->assertSame(1, Declaration::where('student_id', $child->id)->count());
        Storage::disk('local')->assertMissing($first->path);
        $second = Declaration::where('student_id', $child->id)->firstOrFail();
        $this->assertSame('druga.pdf', $second->original_name);
        Storage::disk('local')->assertExists($second->path);
    }

    public function test_remove_deletes_file_and_row(): void
    {
        $child = Student::create(['last_name' => 'Horvat', 'first_name' => 'Ivo']);
        $meta = DeclarationStorage::store(UploadedFile::fake()->create('i.pdf', 10, 'application/pdf'));
        Declaration::create(['student_id' => $child->id, ...$meta]);

        Livewire::test(StudentDeclaration::class, ['studentId' => $child->id])
            ->call('remove');

        $this->assertDatabaseMissing('declarations', ['student_id' => $child->id]);
        Storage::disk('local')->assertMissing($meta['path']);
    }

    public function test_rejects_disallowed_type_and_oversized(): void
    {
        $child = Student::create(['last_name' => 'A', 'first_name' => 'B']);

        Livewire::test(StudentDeclaration::class, ['studentId' => $child->id])
            ->set('file', UploadedFile::fake()->create('virus.exe', 10))
            ->assertHasErrors('file');

        Livewire::test(StudentDeclaration::class, ['studentId' => $child->id])
            ->set('file', UploadedFile::fake()->create('veliko.pdf', 20000, 'application/pdf')) // ~20 MB > 10 MB
            ->assertHasErrors('file');

        $this->assertDatabaseCount('declarations', 0);
    }

    public function test_serve_route_streams_for_trainer_and_blocks_guest(): void
    {
        $child = Student::create(['last_name' => 'Kovač', 'first_name' => 'Ena']);
        $meta = DeclarationStorage::store(UploadedFile::fake()->createWithContent('izjava.pdf', 'SADRŽAJ'));
        Declaration::create(['student_id' => $child->id, ...$meta]);

        $this->get(route('izjava', $child))->assertOk();          // acting trainer (setUp)

        $noDecl = Student::create(['last_name' => 'Bez', 'first_name' => 'Izjave']);
        $this->get(route('izjava', $noDecl))->assertNotFound();

        auth()->logout();
        $this->get(route('izjava', $child))->assertRedirect(route('login'));
    }

    public function test_list_filter_and_count_for_missing_declaration(): void
    {
        $with = Student::create(['last_name' => 'Imajuci', 'first_name' => 'A']);
        Student::create(['last_name' => 'Nemajuci', 'first_name' => 'B']);
        $meta = DeclarationStorage::store(UploadedFile::fake()->create('i.pdf', 10, 'application/pdf'));
        Declaration::create(['student_id' => $with->id, ...$meta]);

        Livewire::test(StudentsList::class)
            ->assertViewHas('noDeclarationCount', 1)
            ->set('noDeclarationOnly', true)
            ->assertViewHas('rows', fn ($rows): bool => $rows->count() === 1 && $rows->first()->last_name === 'Nemajuci');
    }
}
