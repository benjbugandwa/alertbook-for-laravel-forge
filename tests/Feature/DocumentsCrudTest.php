<?php

namespace Tests\Feature;

use App\Livewire\Pages\Documents\Index as DocumentsIndex;
use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_create_replace_download_and_delete_a_document_on_s3(): void
    {
        Storage::fake('s3');
        config(['filesystems.documents' => 's3']);

        $user = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['slug' => 'superviseur'], ['name' => 'Superviseur']);
        $user->roles()->attach($role);

        Livewire::actingAs($user)
            ->test(DocumentsIndex::class)
            ->set('form.doc_name', 'Rapport initial')
            ->set('form.doc_category', 'Rapport')
            ->set('file', UploadedFile::fake()->create('rapport.pdf', 12 * 1024, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $document = Document::firstOrFail();
        $oldPath = $document->file_path;
        Storage::disk('s3')->assertExists($oldPath);

        Livewire::actingAs($user)
            ->test(DocumentsIndex::class)
            ->call('openEdit', $document->id)
            ->set('form.doc_name', 'Rapport final')
            ->set('form.doc_category', 'Rapport')
            ->set('file', UploadedFile::fake()->create('rapport-final.pdf', 12, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $document->refresh();
        Storage::disk('s3')->assertMissing($oldPath);
        Storage::disk('s3')->assertExists($document->file_path);
        $this->assertSame('Rapport final', $document->doc_name);

        Livewire::actingAs($user)
            ->test(DocumentsIndex::class)
            ->call('download', $document->id)
            ->assertFileDownloaded('rapport-final.pdf');

        $document->refresh();
        $this->assertSame(1, $document->download_count);
        $currentPath = $document->file_path;

        Livewire::actingAs($user)
            ->test(DocumentsIndex::class)
            ->call('delete', $document->id);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('s3')->assertMissing($currentPath);
    }
}
