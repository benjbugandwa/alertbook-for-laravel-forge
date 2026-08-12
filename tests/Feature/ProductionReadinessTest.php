<?php

namespace Tests\Feature;

use App\Mail\IncidentAssignedMail;
use App\Notifications\NewAccountPendingActivationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_health_check_is_available_without_sensitive_details(): void
    {
        $response = $this->get('/up');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDontSee('APP_KEY')
            ->assertDontSee('DB_PASSWORD');
    }

    public function test_dangerous_diagnostic_routes_are_not_registered(): void
    {
        $uris = collect(Route::getRoutes())->map->uri();

        $this->assertNotContains('phpinfo', $uris);
        $this->assertNotContains('api/fix-sequence', $uris);
        $this->assertNotContains('whoami', $uris);
    }

    public function test_production_integrations_are_environment_configurable(): void
    {
        $this->assertSame('resend', config('mail.mailers.resend.transport'));
        $this->assertArrayHasKey('documents', config('filesystems'));
        $this->assertArrayHasKey('pgsql', config('database.connections'));
    }

    public function test_non_critical_mail_is_queueable(): void
    {
        $this->assertContains(ShouldQueue::class, class_implements(IncidentAssignedMail::class));
        $this->assertContains(ShouldQueue::class, class_implements(NewAccountPendingActivationNotification::class));
    }
}
