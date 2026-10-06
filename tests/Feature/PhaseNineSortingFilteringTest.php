<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseNineSortingFilteringTest extends TestCase
{
    public function test_my_drive_controls_expose_supported_filters_and_sort_options(): void
    {
        $user = $this->approvedUser();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('x-data="driveApp('.$user->id.')"', false)
            ->assertSee('Filter My Drive by type')
            ->assertSee('Sort My Drive')
            ->assertSee('All types')
            ->assertSee('Folders')
            ->assertSee('Images')
            ->assertSee('Videos')
            ->assertSee('Audio')
            ->assertSee('Documents')
            ->assertSee('Archives')
            ->assertSee('Modified')
            ->assertSee('Ascending')
            ->assertSee('No items match this filter');
    }

    public function test_drive_listing_provides_metadata_required_for_sorting_and_filtering(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        $disk->put($root.'/photo.jpg', str_repeat('i', 4));
        $disk->put($root.'/report.pdf', str_repeat('d', 8));
        $disk->put($root.'/bundle.zip', str_repeat('z', 12));
        $disk->makeDirectory($root.'/Folder');

        $response = $this->actingAs($user)
            ->getJson('/api/files')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'photo.jpg',
                'category' => 'image',
                'size' => 4,
            ])
            ->assertJsonFragment([
                'name' => 'report.pdf',
                'category' => 'pdf',
                'size' => 8,
            ])
            ->assertJsonFragment([
                'name' => 'bundle.zip',
                'category' => 'archive',
                'size' => 12,
            ])
            ->assertJsonFragment([
                'name' => 'Folder',
                'category' => 'folder',
                'is_dir' => true,
            ]);

        foreach ($response->json('items') as $item) {
            $this->assertArrayHasKey('modified_at', $item);
            $this->assertArrayHasKey('human_size', $item);
        }
    }

    public function test_sort_and_filter_preferences_are_user_scoped_and_defensively_loaded(): void
    {
        $script = file_get_contents(resource_path('js/drive.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('jhn_drive_${userId}_${name}', $script);
        $this->assertStringContainsString("readPreference('filter'", $script);
        $this->assertStringContainsString("writePreference('sort'", $script);
        $this->assertStringContainsString('if (left.is_dir !== right.is_dir) return left.is_dir ? -1 : 1;', $script);
        $this->assertStringContainsString("['document', 'pdf', 'code'].includes(item.category)", $script);
        $this->assertStringContainsString('localeCompare(right.name, undefined, { numeric: true', $script);
    }

    private function approvedUser(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
