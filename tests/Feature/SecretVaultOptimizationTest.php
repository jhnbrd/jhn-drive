<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecretVaultOptimizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set PIN hash in config for tests
        config(['secret.enabled' => true]);
        config(['secret.pin_hash' => password_hash('0318', PASSWORD_BCRYPT)]);
        config(['secret.session_ttl_minutes' => 120]);
    }

    public function test_secret_pin_verification_and_vault_access(): void
    {
        $res = $this->post('/secret', ['pin' => '0318']);
        $res->assertRedirect(route('secret.vault'));
        $res->assertSessionHas('secret_vault_auth', true);

        // Vault view renders with 200
        $vaultRes = $this->withSession([
            'secret_vault_auth' => true,
            'secret_vault_granted_at' => now()->timestamp,
        ])->get('/secret/vault');

        $vaultRes->assertStatus(200);
        $vaultRes->assertSee('Secret Vault');
    }

    public function test_secret_upload_and_thumbnail_and_stream(): void
    {
        // Fake a video file
        $videoContent = "FAKE_MP4_HEADER_" . str_repeat("A", 2048);
        $file = UploadedFile::fake()->createWithContent('sample_clip.mp4', $videoContent);

        $fakeThumb = 'data:image/jpeg;base64,' . base64_encode('FAKE_JPEG_THUMB_BYTES_1234567890');

        $res = $this->withSession([
            'secret_vault_auth' => true,
            'secret_vault_granted_at' => now()->timestamp,
        ])->postJson('/secret/upload', [
            'file' => $file,
            'thumbnail' => $fakeThumb,
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        // Thumbnail route serves the dedicated thumbnail
        $thumbRes = $this->withSession([
            'secret_vault_auth' => true,
            'secret_vault_granted_at' => now()->timestamp,
        ])->get('/secret/thumbnail?file=sample_clip.mp4');

        $thumbRes->assertStatus(200);
        $this->assertEquals('image/jpeg', $thumbRes->headers->get('Content-Type'));

        // Stream route serves preview with range requests
        $previewRes = $this->withSession([
            'secret_vault_auth' => true,
            'secret_vault_granted_at' => now()->timestamp,
        ])->withHeaders([
            'Range' => 'bytes=0-100'
        ])->get('/secret/preview?file=sample_clip.mp4');

        $previewRes->assertStatus(206);
        $this->assertTrue($previewRes->headers->has('Content-Range'));
        $this->assertEquals('bytes', $previewRes->headers->get('Accept-Ranges'));

        // Test lock purges session
        $lockRes = $this->withSession([
            'secret_vault_auth' => true,
            'secret_vault_granted_at' => now()->timestamp,
        ])->postJson('/secret/lock');

        $lockRes->assertStatus(200);
        $lockRes->assertSessionMissing('secret_vault_auth');
    }
}