<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookSsrfTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_loopback_url_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks', [
            'url' => 'http://127.0.0.1:9200/hook',
            'is_enabled' => '1',
        ])->assertSessionHasErrors('url');

        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_saving_the_cloud_metadata_endpoint_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks', [
            'url' => 'http://169.254.169.254/latest/meta-data/',
            'is_enabled' => '1',
        ])->assertSessionHasErrors('url');

        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_saving_a_private_range_url_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks', [
            'url' => 'http://10.0.0.5/hook',
            'is_enabled' => '1',
        ])->assertSessionHasErrors('url');

        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_test_send_refuses_a_private_url_saved_directly(): void
    {
        Http::fake();

        $user = User::factory()->create();
        // Bypass store() validation — simulates a URL saved before the guard
        // existed, or a host that has since started resolving to a private IP.
        $user->webhook()->create([
            'url' => 'http://169.254.169.254/latest/meta-data/',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)->post('/integrations/webhooks/test')
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseHas('webhook_deliveries', ['successful' => false]);
    }
}
