<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseSevenMoveCopyTest extends TestCase
{
    public function test_move_resolves_collisions_and_rewrites_share_paths(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        $disk->put($root.'/source/report.txt', 'move me');
        $disk->put($root.'/destination/report.txt', 'existing');

        $share = SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-seven-move',
            'file_path' => $root.'/source/report.txt',
            'is_folder' => false,
        ]);

        $this->actingAs($user)
            ->postJson('/api/transfer', [
                'operation' => 'move',
                'paths' => ['source/report.txt'],
                'destination' => 'destination',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('items.0.path', 'destination/report (1).txt');

        $disk->assertMissing($root.'/source/report.txt');
        $this->assertSame('move me', $disk->get($root.'/destination/report (1).txt'));
        $this->assertSame(
            $root.'/destination/report (1).txt',
            $share->fresh()->file_path,
        );
    }

    public function test_moving_a_folder_preserves_shares_for_it_and_its_children(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/projects/demo/readme.md', '# Demo');
        $disk->makeDirectory($root.'/archive');

        $folderShare = SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-seven-folder',
            'file_path' => $root.'/projects',
            'is_folder' => true,
        ]);
        $childShare = SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-seven-child',
            'file_path' => $root.'/projects/demo/readme.md',
            'is_folder' => false,
        ]);

        $this->actingAs($user)
            ->postJson('/api/transfer', [
                'operation' => 'move',
                'paths' => ['projects'],
                'destination' => 'archive',
            ])
            ->assertOk()
            ->assertJsonPath('items.0.path', 'archive/projects');

        $disk->assertExists($root.'/archive/projects/demo/readme.md');
        $this->assertSame($root.'/archive/projects', $folderShare->fresh()->file_path);
        $this->assertSame($root.'/archive/projects/demo/readme.md', $childShare->fresh()->file_path);
    }

    public function test_copy_is_recursive_collision_safe_and_does_not_duplicate_shares(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/photos/raw/image.jpg', 'image');
        $disk->put($root.'/backup/photos/old.txt', 'old');

        SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-seven-copy',
            'file_path' => $root.'/photos',
            'is_folder' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/transfer', [
                'operation' => 'copy',
                'paths' => ['photos'],
                'destination' => 'backup',
            ])
            ->assertOk()
            ->assertJsonPath('items.0.path', 'backup/photos (1)');

        $disk->assertExists($root.'/photos/raw/image.jpg');
        $this->assertSame('image', $disk->get($root.'/backup/photos (1)/raw/image.jpg'));
        $this->assertSame(1, SharedLink::where('user_id', $user->id)->count());
        $this->assertDatabaseMissing('shared_links', [
            'file_path' => $root.'/backup/photos (1)',
        ]);
    }

    public function test_folder_cannot_be_moved_or_copied_into_itself_or_a_descendant(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/folder/child/file.txt', 'safe');

        foreach (['move', 'copy'] as $operation) {
            $this->actingAs($user)
                ->postJson('/api/transfer', [
                    'operation' => $operation,
                    'paths' => ['folder'],
                    'destination' => 'folder/child',
                ])
                ->assertUnprocessable()
                ->assertJsonPath('success', false);
        }

        $disk->assertExists($root.'/folder/child/file.txt');
    }

    public function test_nested_selection_and_cross_user_paths_are_rejected(): void
    {
        $user = $this->approvedUser();
        $other = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/folder/child.txt', 'child');
        $disk->put($other->storageRelativePath().'/private.txt', 'private');

        $this->actingAs($user)
            ->postJson('/api/transfer', [
                'operation' => 'move',
                'paths' => ['folder', 'folder/child.txt'],
                'destination' => '',
            ])
            ->assertUnprocessable();

        $this->postJson('/api/transfer', [
            'operation' => 'copy',
            'paths' => ['../'.$other->id.'/private.txt'],
            'destination' => '',
        ])->assertForbidden();

        $disk->assertExists($root.'/folder/child.txt');
        $disk->assertExists($other->storageRelativePath().'/private.txt');
    }

    public function test_transfer_validation_limits_bulk_operations(): void
    {
        $paths = array_map(
            static fn (int $index): string => 'file-'.$index.'.txt',
            range(1, 101),
        );

        $this->actingAs($this->approvedUser())
            ->postJson('/api/transfer', [
                'operation' => 'move',
                'paths' => $paths,
                'destination' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('paths');
    }

    public function test_drive_page_exposes_item_and_bulk_transfer_actions_with_folder_picker(): void
    {
        $this->actingAs($this->approvedUser())
            ->get('/')
            ->assertOk()
            ->assertSee("openTransferDialog(item, 'move')", false)
            ->assertSee("openTransferDialog(selectedItems, 'copy')", false)
            ->assertSee('Choose a destination')
            ->assertSee('Destination:')
            ->assertSee('aria-labelledby="transfer-dialog-title"', false);
    }

    private function approvedUser(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
