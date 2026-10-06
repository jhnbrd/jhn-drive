<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\TrashedItem;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseSixTrashTest extends TestCase
{
    public function test_delete_moves_a_file_to_hidden_trash_and_revokes_its_share(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/docs/report.txt', 'recover me');

        SharedLink::create([
            'user_id' => $user->id,
            'token' => 'trash-share-token',
            'file_path' => $root.'/docs/report.txt',
            'is_folder' => false,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/delete', ['path' => 'docs/report.txt'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $item = TrashedItem::whereBelongsTo($user)->sole();

        $disk->assertMissing($root.'/docs/report.txt');
        $disk->assertExists($root.'/.trash/'.$item->storage_name);
        $this->assertDatabaseMissing('shared_links', ['token' => 'trash-share-token']);

        $this->getJson('/api/files')
            ->assertOk()
            ->assertJsonMissing(['name' => '.trash']);
    }

    public function test_restore_recreates_parents_and_resolves_name_collisions(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/archive/report.txt', 'trashed version');

        $this->actingAs($user)->deleteJson('/api/delete', ['path' => 'archive/report.txt'])->assertOk();
        $item = TrashedItem::whereBelongsTo($user)->sole();

        $disk->deleteDirectory($root.'/archive');
        $disk->put($root.'/archive/report.txt', 'new version');

        $this->postJson('/api/trash/'.$item->id.'/restore')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('collided', true)
            ->assertJsonPath('path', 'archive/report (restored 1).txt');

        $this->assertSame('trashed version', $disk->get($root.'/archive/report (restored 1).txt'));
        $this->assertDatabaseMissing('trashed_items', ['id' => $item->id]);
    }

    public function test_folder_restore_preserves_nested_contents(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/projects/demo/readme.md', '# Demo');

        $this->actingAs($user)->deleteJson('/api/delete', ['path' => 'projects'])->assertOk();
        $item = TrashedItem::whereBelongsTo($user)->sole();

        $this->postJson('/api/trash/'.$item->id.'/restore')
            ->assertOk()
            ->assertJsonPath('path', 'projects');

        $this->assertSame('# Demo', $disk->get($root.'/projects/demo/readme.md'));
    }

    public function test_trash_is_private_and_cannot_be_accessed_through_drive_paths(): void
    {
        $owner = $this->approvedUser();
        $other = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $owner->storageRelativePath();
        $disk->put($root.'/private.txt', 'private');

        $this->actingAs($owner)->deleteJson('/api/delete', ['path' => 'private.txt'])->assertOk();
        $item = TrashedItem::whereBelongsTo($owner)->sole();

        $this->actingAs($other)
            ->postJson('/api/trash/'.$item->id.'/restore')
            ->assertNotFound();

        $this->deleteJson('/api/trash/'.$item->id)
            ->assertNotFound();

        $this->actingAs($owner)
            ->getJson('/api/files?path='.urlencode('.trash'))
            ->assertNotFound();

        $this->getJson('/api/files?path='.urlencode($root.'/.trash'))
            ->assertNotFound();
    }

    public function test_permanent_delete_and_empty_trash_are_scoped_to_the_current_user(): void
    {
        $owner = $this->approvedUser();
        $other = $this->approvedUser();
        $disk = Storage::disk('local_drive');

        $disk->put($owner->storageRelativePath().'/one.txt', 'one');
        $disk->put($owner->storageRelativePath().'/two.txt', 'two');
        $disk->put($other->storageRelativePath().'/keep.txt', 'keep');

        $this->actingAs($owner)->deleteJson('/api/delete', ['path' => 'one.txt'])->assertOk();
        $this->deleteJson('/api/delete', ['path' => 'two.txt'])->assertOk();
        $this->actingAs($other)->deleteJson('/api/delete', ['path' => 'keep.txt'])->assertOk();

        $first = TrashedItem::whereBelongsTo($owner)->oldest('id')->firstOrFail();
        $this->actingAs($owner)->deleteJson('/api/trash/'.$first->id)->assertOk();
        $this->assertDatabaseMissing('trashed_items', ['id' => $first->id]);

        $this->deleteJson('/api/trash')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, TrashedItem::whereBelongsTo($owner)->count());
        $this->assertSame(1, TrashedItem::whereBelongsTo($other)->count());
    }

    public function test_trashed_files_still_count_toward_quota_but_do_not_appear_in_recent_items(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/visible.txt', '12345');

        $this->actingAs($user)->deleteJson('/api/delete', ['path' => 'visible.txt'])->assertOk();

        $this->getJson('/api/home?refresh=1')
            ->assertOk()
            ->assertJsonPath('storage.used_bytes', 5)
            ->assertJsonPath('storage.file_count', 1)
            ->assertJsonMissing(['name' => 'visible.txt']);
    }

    public function test_drive_page_exposes_recovery_and_confirmed_permanent_actions(): void
    {
        $this->actingAs($this->approvedUser())
            ->get('/')
            ->assertOk()
            ->assertSee('Trash')
            ->assertSee('Restore')
            ->assertSee('Empty Trash')
            ->assertSee('Move to Trash')
            ->assertSee('This cannot be undone.');
    }

    private function approvedUser(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
