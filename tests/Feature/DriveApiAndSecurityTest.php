<?php

namespace Tests\Feature;

use App\Models\SharedLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriveApiAndSecurityTest extends TestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create approved superadmin
        $this->admin = User::create([
            'email' => 'admin@jhnbrd.com',
            'name' => 'Admin User',
            'password' => Hash::make('password123'),
            'is_superadmin' => true,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->admin->ensureStorageDirectoryExists();

        // Create approved standard user
        $this->user = User::create([
            'email' => 'user@jhnbrd.com',
            'name' => 'Regular User',
            'password' => Hash::make('password123'),
            'is_superadmin' => false,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        $this->user->ensureStorageDirectoryExists();

        // Put sample file in user's isolated directory
        $disk = Storage::disk('local_drive');
        $disk->put($this->user->storageRelativePath() . '/test_sample.txt', 'This is a test file for regular user.');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_access_request_submission_creates_pending_user(): void
    {
        $response = $this->post('/request-access', [
            'name' => 'Alice Applicant',
            'email' => 'alice@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'request_note' => 'Video production portfolio storage',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Access Request Submitted!');

        $user = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isPending());
        $this->assertFalse($user->isApproved());
    }

    public function test_pending_user_cannot_login(): void
    {
        $pendingUser = User::create([
            'name' => 'Bob Pending',
            'email' => 'bob@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
        ]);

        $response = $this->post('/login', [
            'email' => 'bob@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_superadmin_can_approve_pending_user(): void
    {
        $pendingUser = User::create([
            'name' => 'Charlie Pending',
            'email' => 'charlie@example.com',
            'password' => Hash::make('password123'),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/users/' . $pendingUser->id . '/approve');
        $response->assertRedirect();

        $pendingUser->refresh();
        $this->assertTrue($pendingUser->isApproved());
        $this->assertTrue(is_dir($pendingUser->storageFullPath()));
    }

    public function test_authenticated_approved_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/');
        $response->assertStatus(200);
        $response->assertSee('JHN Drive');
        $response->assertSee('Regular User');
    }

    public function test_api_files_lists_user_isolated_directory(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/files');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'current_path',
            'items',
        ]);
        $response->assertJsonFragment(['name' => 'test_sample.txt']);
    }

    public function test_path_traversal_is_blocked_for_user(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/files?path=../../');
        $response->assertStatus(403);
    }

    public function test_user_cannot_download_other_user_file_via_traversal(): void
    {
        // Try to escape to another user's folder or root
        $response = $this->actingAs($this->user)->get('/api/download?path=../' . $this->admin->id . '/secret.txt');
        $response->assertStatus(403);
    }

    public function test_file_upload_and_download_scoped_to_user(): void
    {
        $fakeFile = UploadedFile::fake()->create('project_notes.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson('/api/upload', [
            'path' => '',
            'files' => [$fakeFile],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // File should exist inside user's isolated folder
        $this->assertTrue(Storage::disk('local_drive')->exists($this->user->storageRelativePath() . '/project_notes.pdf'));

        // Test download
        $downloadRes = $this->actingAs($this->user)->get('/api/download?path=project_notes.pdf');
        $downloadRes->assertStatus(200);
    }

    public function test_public_share_token_accessible_without_authentication(): void
    {
        // Create share token as user
        $response = $this->actingAs($this->user)->postJson('/api/share', [
            'path' => 'test_sample.txt',
        ]);

        $response->assertStatus(200);
        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Sign out
        auth()->logout();

        // Guest access to public landing page
        $landingRes = $this->get('/s/' . $token);
        $landingRes->assertStatus(200);
        $landingRes->assertSee('test_sample.txt');
        $landingRes->assertSee('Download File');

        // Guest download
        $downloadRes = $this->get('/s/' . $token . '/download');
        $downloadRes->assertStatus(200);

        $link = SharedLink::where('token', $token)->first();
        $this->assertEquals(1, $link->downloads_count);
    }

    public function test_storage_quota_stats_reflect_20gb(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'used_bytes',
            'quota_bytes',
            'free_bytes',
            'percent_used',
            'used_human',
            'quota_human',
        ]);
        $this->assertEquals(User::STORAGE_QUOTA_BYTES, $response->json('quota_bytes'));
        $this->assertEquals('20 GB', $response->json('quota_human'));
    }
}
