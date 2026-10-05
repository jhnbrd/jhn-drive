<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class PhaseThreeResponsiveUiTest extends TestCase
{
    public function test_drive_shell_renders_responsive_navigation_and_accessible_controls(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('aria-label="Open navigation"', false);
        $response->assertSee('aria-label="Primary navigation"', false);
        $response->assertSee('Secret Vault');
        $response->assertSee('mobileSidebarOpen', false);
        $response->assertDontSee('fonts.googleapis.com', false);
    }

    public function test_login_uses_self_hosted_visual_assets_only(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Welcome to JHN Drive');
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertSee('name="viewport"', false);
    }
}
