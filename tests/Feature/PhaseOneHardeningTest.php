<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseOneHardeningTest extends TestCase
{
    private function approvedUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'approved',
            'approved_at' => now(),
            'is_superadmin' => false,
        ], $attributes));
    }

    public function test_protected_routes_recheck_approval_on_every_request(): void
    {
        $user = $this->approvedUser();
        $this->actingAs($user)->getJson('/api/stats')->assertOk();

        $user->forceFill(['status' => 'rejected', 'approved_at' => null])->save();

        $this->getJson('/api/stats')
            ->assertForbidden()
            ->assertJson(['message' => 'Your account no longer has access to JHN Drive.']);

        $this->assertGuest();
    }

    public function test_upload_route_includes_csrf_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('drive.upload');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());

        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringNotContainsString('validateCsrfTokens(except', $bootstrap);
    }

    public function test_login_is_rate_limited(): void
    {
        $email = 'rate-limit-'.uniqid().'@example.com';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        $this->from('/login')->post('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_stats_expose_actual_upload_limits(): void
    {
        $response = $this->actingAs($this->approvedUser())->getJson('/api/stats');

        $response->assertOk()->assertJsonStructure([
            'upload_limits' => [
                'max_file_bytes',
                'max_request_bytes',
                'max_files_per_request',
                'max_file_human',
                'max_request_human',
            ],
        ]);

        $this->assertGreaterThan(0, $response->json('upload_limits.max_file_bytes'));
        $this->assertGreaterThan(0, $response->json('upload_limits.max_request_bytes'));
    }

    public function test_duplicate_upload_is_renamed_instead_of_overwritten(): void
    {
        $user = $this->approvedUser();
        $directory = $user->storageRelativePath();
        Storage::disk('local_drive')->put($directory.'/report.txt', 'original');

        $response = $this->actingAs($user)->postJson('/api/upload', [
            'path' => '',
            'files' => [UploadedFile::fake()->createWithContent('report.txt', 'replacement')],
        ]);

        $response->assertOk()
            ->assertJsonPath('files.0.name', 'report (1).txt');

        $this->assertSame('original', Storage::disk('local_drive')->get($directory.'/report.txt'));
        $this->assertSame('replacement', Storage::disk('local_drive')->get($directory.'/report (1).txt'));
    }

    public function test_svg_preview_is_forced_to_download_with_defensive_headers(): void
    {
        $user = $this->approvedUser();
        Storage::disk('local_drive')->put(
            $user->storageRelativePath().'/payload.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
        );

        $response = $this->actingAs($user)->get('/api/preview?path=payload.svg');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/octet-stream');
        $response->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('content-disposition'));
    }

    public function test_public_folder_share_rejects_traversal_to_sibling_path(): void
    {
        $user = $this->approvedUser();
        $disk = Storage::disk('local_drive');
        $disk->put($user->storageRelativePath().'/shared/readme.txt', 'public');
        $disk->put($user->storageRelativePath().'/private/secret.txt', 'private');

        $link = SharedLink::forPath($user->storageRelativePath().'/shared', $user->id, true);

        $this->get('/s/'.$link->token.'/download?path=../private/secret.txt')
            ->assertNotFound();
    }
}
