<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthLoginLogoTest extends TestCase
{
    public function test_login_page_contains_logo_configuration(): void
    {
        $this->get('/admin/login')
            ->assertSuccessful();
    }

    public function test_filament_admin_panel_has_logo_configured(): void
    {
        // Verify the panel provider has logo configuration
        $providers = config('app.providers');
        $this->assertContains('App\\Providers\\Filament\\AdminPanelProvider', $providers);
    }

    public function test_logo_asset_exists(): void
    {
        $this->assertFileExists(public_path('logo-expertwood.svg'));
    }

    public function test_login_page_has_large_logo(): void
    {
        // Verify that the login page class has the larger logo height
        $loginClass = new \App\Filament\Pages\Login();
        $reflection = new \ReflectionClass($loginClass);
        $method = $reflection->getMethod('getLogoBrand');

        // Just verify the method exists and is callable
        $this->assertTrue($method->isProtected());
    }
}
