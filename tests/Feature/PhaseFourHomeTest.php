<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseFourHomeTest extends TestCase
{
    public function test_home_summary_combines_recent_files_categories_storage_and_shares(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        $disk->put($root.'/photos/sunrise.jpg', str_repeat('i', 120));
        $disk->put($root.'/notes/report.pdf', str_repeat('d', 80));
        $disk->put($root.'/music/theme.mp3', str_repeat('a', 40));
        $disk->put($root.'/.trash/ignored.zip', str_repeat('x', 500));

        SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-four-share',
            'file_path' => $root.'/notes/report.pdf',
            'is_folder' => false,
            'downloads_count' => 3,
        ]);

        $response = $this->actingAs($user)->getJson('/api/home');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('storage.used_bytes', 240)
            ->assertJsonPath('storage.file_count', 3)
            ->assertJsonPath('shared.0.name', 'report.pdf')
            ->assertJsonPath('shared.0.downloads_count', 3)
            ->assertJsonFragment(['name' => 'sunrise.jpg'])
            ->assertJsonFragment(['name' => 'theme.mp3'])
            ->assertJsonFragment(['key' => 'image', 'bytes' => 120, 'count' => 1])
            ->assertJsonFragment(['key' => 'document', 'bytes' => 80, 'count' => 1])
            ->assertJsonFragment(['key' => 'audio', 'bytes' => 40, 'count' => 1]);

        $this->assertCount(6, $response->json('categories'));
    }

    public function test_home_cache_can_be_refreshed_and_drive_mutations_invalidate_it(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        $disk->put($root.'/first.txt', 'first');

        $this->actingAs($user)
            ->getJson('/api/home')
            ->assertJsonPath('storage.file_count', 1);

        $disk->put($root.'/manual.txt', 'manual');

        $this->getJson('/api/home')
            ->assertJsonPath('storage.file_count', 1);

        $this->getJson('/api/home?refresh=1')
            ->assertJsonPath('storage.file_count', 2)
            ->assertJsonFragment(['name' => 'manual.txt']);

        $this->postJson('/api/upload', [
            'path' => '',
            'files' => [UploadedFile::fake()->create('uploaded.txt', 1, 'text/plain')],
        ])->assertOk()->assertJsonPath('success', true);

        $this->getJson('/api/home')
            ->assertJsonPath('storage.file_count', 3)
            ->assertJsonFragment(['name' => 'uploaded.txt']);
    }

    public function test_drive_page_renders_home_as_a_distinct_accessible_section(): void
    {
        $user = User::factory()->create([
            'name' => 'Home Tester',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Welcome back, Home')
            ->assertSee('Quick actions')
            ->assertSee('Recently modified')
            ->assertSee('Recently shared')
            ->assertSee('aria-labelledby="storage-heading"', false)
            ->assertSee("viewSection === 'home'", false);
    }

    public function test_home_endpoint_requires_an_approved_authenticated_user(): void
    {
        $this->getJson('/api/home')->assertUnauthorized();

        $pending = User::factory()->create(['status' => 'pending']);

        $this->actingAs($pending)
            ->getJson('/api/home')
            ->assertForbidden();
    }
}
