<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use App\Services\DriveSearchService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseEightSearchTest extends TestCase
{
    public function test_search_is_recursive_case_insensitive_and_returns_useful_metadata(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/projects/Deep Folder/Quarterly Report.pdf', 'report');

        SharedLink::create([
            'user_id' => $user->id,
            'token' => 'phase-eight-search',
            'file_path' => $root.'/projects/Deep Folder/Quarterly Report.pdf',
            'is_folder' => false,
            'downloads_count' => 4,
        ]);

        $this->actingAs($user)
            ->getJson('/api/search?q='.urlencode('REPORT'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.name', 'Quarterly Report.pdf')
            ->assertJsonPath('items.0.path', 'projects/Deep Folder/Quarterly Report.pdf')
            ->assertJsonPath('items.0.location', 'My Drive / projects / Deep Folder')
            ->assertJsonPath('items.0.category', 'pdf')
            ->assertJsonPath('items.0.share_token', 'phase-eight-search')
            ->assertJsonPath('items.0.downloads_count', 4);
    }

    public function test_search_filters_supported_file_categories_and_folders(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        $disk->put($root.'/find-image.jpg', 'image');
        $disk->put($root.'/find-video.mp4', 'video');
        $disk->put($root.'/find-audio.mp3', 'audio');
        $disk->put($root.'/find-document.pdf', 'pdf');
        $disk->put($root.'/find-code.php', '<?php');
        $disk->put($root.'/find-archive.zip', 'zip');
        $disk->makeDirectory($root.'/find-folder');

        $expectations = [
            'image' => ['find-image.jpg'],
            'video' => ['find-video.mp4'],
            'audio' => ['find-audio.mp3'],
            'document' => ['find-code.php', 'find-document.pdf'],
            'archive' => ['find-archive.zip'],
            'folder' => ['find-folder'],
        ];

        foreach ($expectations as $type => $names) {
            $response = $this->actingAs($user)
                ->getJson('/api/search?q=find&type='.$type)
                ->assertOk()
                ->assertJsonCount(count($names), 'items');

            $this->assertSame($names, array_column($response->json('items'), 'name'));
        }
    }

    public function test_search_sorting_supports_size_and_direction(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();
        $disk->put($root.'/match-small.txt', '1');
        $disk->put($root.'/nested/match-large.txt', str_repeat('x', 20));

        $response = $this->actingAs($user)
            ->getJson('/api/search?q=match&sort=size&direction=desc')
            ->assertOk();

        $this->assertSame(
            ['match-large.txt', 'match-small.txt'],
            array_column($response->json('items'), 'name'),
        );
    }

    public function test_search_excludes_trash_and_other_users_files(): void
    {
        $user = $this->approvedUser();
        $other = $this->approvedUser();
        $disk = Storage::disk('local_drive');

        $disk->put($user->storageRelativePath().'/visible-secret.txt', 'visible');
        $disk->put($user->storageRelativePath().'/.trash/hidden-secret.txt', 'hidden');
        $disk->put($other->storageRelativePath().'/other-secret.txt', 'other');

        $response = $this->actingAs($user)
            ->getJson('/api/search?q=secret')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.name', 'visible-secret.txt');

        $response->assertJsonMissing(['name' => 'hidden-secret.txt'])
            ->assertJsonMissing(['name' => 'other-secret.txt']);
    }

    public function test_search_results_are_bounded_and_report_truncation(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $root = $user->storageRelativePath();

        for ($index = 0; $index <= DriveSearchService::MAX_RESULTS; $index++) {
            $disk->put($root.'/bounded-result-'.$index.'.txt', '');
        }

        $this->actingAs($user)
            ->getJson('/api/search?q=bounded-result')
            ->assertOk()
            ->assertJsonPath('count', DriveSearchService::MAX_RESULTS)
            ->assertJsonPath('limit', DriveSearchService::MAX_RESULTS)
            ->assertJsonPath('truncated', true)
            ->assertJsonCount(DriveSearchService::MAX_RESULTS, 'items');
    }

    public function test_search_validates_queries_and_requires_approved_authentication(): void
    {
        $this->getJson('/api/search?q=file')->assertUnauthorized();

        $pending = User::factory()->create(['status' => 'pending']);
        $this->actingAs($pending)
            ->getJson('/api/search?q=file')
            ->assertForbidden();

        $this->actingAs($this->approvedUser())
            ->getJson('/api/search?q='.urlencode('   '))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');

        $this->getJson('/api/search?q=file&type=executable')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_drive_page_exposes_server_search_filters_sorting_and_results(): void
    {
        $this->actingAs($this->approvedUser())
            ->get('/')
            ->assertOk()
            ->assertSee('Search entire Drive')
            ->assertSee('@input.debounce.350ms="handleSearchInput()"', false)
            ->assertSee('Find files and folders by name in every Drive folder.')
            ->assertSee('Showing the first 500 matches')
            ->assertSee('Destination:')
            ->assertSee("viewSection === 'search'", false);
    }

    private function approvedUser(): User
    {
        return User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
