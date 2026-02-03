<?php

namespace Tests\Feature;

use App\Filament\Pages\LearnToAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentLearnToAdminPageTest extends TestCase
{
    use RefreshDatabase;

    private function setLearnToAdminFile(string $content): string
    {
        $path = storage_path('framework/testing/learn-to-admin.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        config()->set('admin.learn_to_admin_path', $path);

        return $path;
    }

    public function test_editor_can_view_learn_to_admin_page(): void
    {
        $this->setLearnToAdminFile("# Заголовок\n\nUNIQUE_TOKEN_123");

        $user = User::factory()->create(['role' => 'editor']);

        $this->actingAs($user)
            ->get('/admin/learn-to-admin')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('fi-fo-markdown-editor', false)
            ->assertSee('UNIQUE_TOKEN_123');
    }

    public function test_admin_can_edit_learn_to_admin_file(): void
    {
        $path = $this->setLearnToAdminFile("# Old\n\nContent");

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(LearnToAdmin::class)
            ->assertActionVisible('edit')
            ->callAction('edit', [
                'content' => "# New\n\nUpdated",
            ]);

        $this->assertSame("# New\n\nUpdated", File::get($path));
    }

    public function test_editor_cannot_edit_learn_to_admin_file(): void
    {
        $this->setLearnToAdminFile("# Old\n\nContent");

        $editor = User::factory()->create(['role' => 'editor']);

        Livewire::actingAs($editor)
            ->test(LearnToAdmin::class)
            ->assertActionHidden('edit');
    }
}
