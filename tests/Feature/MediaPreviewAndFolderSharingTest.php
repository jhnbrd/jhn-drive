<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaPreviewAndFolderSharingTest extends TestCase
{
    public function test_user_can_share_folder_and_list_contents_publicly()
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'is_superadmin' => false,
        ]);

        // Create folder and file inside it
        $userDiskDir = $user->storageRelativePath() . '/photos';
        Storage::disk('local_drive')->makeDirectory($userDiskDir);
        Storage::disk('local_drive')->put($userDiskDir . '/landscape.jpg', 'fake-image-content');

        // Share the folder
        $response = $this->actingAs($user)->postJson('/api/share', [
            'path' => 'photos',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_folder' => true,
            ]);

        $token = $response->json('token');
        $this->assertNotNull($token);

        // Access shared folder publicly (unauthenticated)
        $publicRes = $this->get('/s/' . $token);
        $publicRes->assertStatus(200);
        $publicRes->assertSee('landscape.jpg');
        $publicRes->assertSee('Download as ZIP');
    }

    public function test_user_can_download_shared_folder_as_zip()
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'is_superadmin' => false,
        ]);

        $userDiskDir = $user->storageRelativePath() . '/docs';
        Storage::disk('local_drive')->makeDirectory($userDiskDir);
        Storage::disk('local_drive')->put($userDiskDir . '/readme.txt', 'hello zip world');

        $shareRes = $this->actingAs($user)->postJson('/api/share', [
            'path' => 'docs',
        ]);

        $token = $shareRes->json('token');

        // Public ZIP download
        $zipRes = $this->get('/s/' . $token . '/zip');
        $zipRes->assertStatus(200);
        $zipRes->assertHeader('content-type', 'application/zip');
    }

    public function test_user_can_preview_media_with_range_requests()
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'is_superadmin' => false,
        ]);

        $filePath = $user->storageRelativePath() . '/sample.mp4';
        Storage::disk('local_drive')->put($filePath, '0123456789ABCDEF');

        // Full preview
        $previewRes = $this->actingAs($user)->get('/api/preview?path=sample.mp4');
        $previewRes->assertStatus(200);
        $previewRes->assertHeader('content-type', 'video/mp4');
        $previewRes->assertHeader('accept-ranges', 'bytes');

        // Range request preview
        $rangeRes = $this->actingAs($user)->withHeaders([
            'Range' => 'bytes=0-5',
        ])->get('/api/preview?path=sample.mp4');

        $rangeRes->assertStatus(206);
        $rangeRes->assertHeader('content-range', 'bytes 0-5/16');
    }

    public function test_user_can_unshare_file_or_folder()
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'is_superadmin' => false,
        ]);

        $filePath = $user->storageRelativePath() . '/notes.txt';
        Storage::disk('local_drive')->put($filePath, 'private notes');

        $shareRes = $this->actingAs($user)->postJson('/api/share', [
            'path' => 'notes.txt',
        ]);
        $token = $shareRes->json('token');

        $this->assertEquals(1, SharedLink::where('token', $token)->count());

        // Unshare
        $unshareRes = $this->actingAs($user)->postJson('/api/unshare', [
            'token' => $token,
        ]);
        $unshareRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(0, SharedLink::where('token', $token)->count());

        // Public access should now 404
        $publicRes = $this->get('/s/' . $token);
        $publicRes->assertStatus(404);
    }

    public function test_api_shared_lists_active_shared_links()
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'is_superadmin' => false,
        ]);

        $filePath = $user->storageRelativePath() . '/shared-file.txt';
        Storage::disk('local_drive')->put($filePath, 'data');

        $this->actingAs($user)->postJson('/api/share', [
            'path' => 'shared-file.txt',
        ]);

        $listRes = $this->actingAs($user)->getJson('/api/shared');
        $listRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(1, $listRes->json('items'));
        $this->assertEquals('shared-file.txt', $listRes->json('items.0.name'));
    }
}
