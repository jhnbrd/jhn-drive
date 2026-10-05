<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use App\Services\ZipArchiveService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PhaseFiveMyDriveTest extends TestCase
{
    public function test_my_drive_renders_selection_actions_and_accessible_details(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Select all visible items')
            ->assertSee('toggleSelection(item, $event)', false)
            ->assertSee('Shift-click a checkbox to select a range')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-labelledby="item-details-title"', false)
            ->assertSee('Delete permanently');
    }

    public function test_file_listing_exposes_details_and_share_download_count(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $root = $user->storageRelativePath();

        Storage::disk('local_drive')->put($root.'/docs/report.pdf', 'report');

        SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-five-report',
            'file_path' => $root.'/docs/report.pdf',
            'is_folder' => false,
            'downloads_count' => 7,
        ]);

        $this->actingAs($user)
            ->getJson('/api/files?path=docs')
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'report.pdf',
                'mime_type' => 'application/pdf',
                'category' => 'pdf',
                'share_token' => 'phase-five-report',
                'downloads_count' => 7,
            ]);
    }

    public function test_selected_files_and_folders_can_be_downloaded_as_a_zip(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        $disk->put($root.'/root.txt', 'root file');
        $disk->put($root.'/folder/nested.txt', 'nested file');

        $response = $this->actingAs($user)->postJson('/api/download-selection', [
            'paths' => ['root.txt', 'folder'],
        ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertDownload();

        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertNotFalse($zip->locateName('root.txt'));
        $this->assertNotFalse($zip->locateName('folder/nested.txt'));
        $zip->close();
    }

    public function test_selection_download_rejects_cross_user_paths_and_large_selection_lists(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $other = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        Storage::disk('local_drive')->put($other->storageRelativePath().'/private.txt', 'private');

        $this->actingAs($user)
            ->postJson('/api/download-selection', [
                'paths' => ['../'.$other->id.'/private.txt'],
            ])
            ->assertForbidden();

        $paths = array_map(
            static fn (int $index): string => 'file-'.$index.'.txt',
            range(1, 101),
        );

        $this->postJson('/api/download-selection', ['paths' => $paths])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('paths');
    }

    public function test_zip_service_rejects_archives_over_the_configured_byte_limit(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $disk = Storage::disk('local_drive');
        $root = $user->storageFullPath();
        $diskPath = $user->storageRelativePath().'/large.bin';

        $disk->put($diskPath, str_repeat('x', 32));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('too large');

        app(ZipArchiveService::class)->createFromPaths(
            $root,
            [$disk->path($diskPath)],
            maxBytes: 16,
        );
    }
}
