<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_requires_authentication(): void
    {
        $this->get('/integrations/webhooks')->assertRedirect('/login');
    }

    public function test_page_renders_and_shows_a_saved_url(): void
    {
        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://hooks.example.test/abc', 'is_enabled' => true]);

        $this->actingAs($user)->get('/integrations/webhooks')
            ->assertOk()
            ->assertSee('Webhooks')
            ->assertSee('https://hooks.example.test/abc');
    }

    public function test_user_can_save_a_webhook_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks', [
            'url' => 'https://1.1.1.1/new',
            'is_enabled' => '1',
        ])->assertRedirect(route('integrations.webhooks'));

        $this->assertDatabaseHas('webhooks', [
            'user_id' => $user->id,
            'url' => 'https://1.1.1.1/new',
            'is_enabled' => true,
        ]);
    }

    public function test_saving_a_url_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks', ['url' => ''])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_saving_rejects_an_invalid_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks', ['url' => 'not-a-url'])
            ->assertSessionHasErrors('url');

        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_saving_updates_the_existing_webhook_rather_than_duplicating(): void
    {
        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://old.example.test', 'is_enabled' => true]);

        $this->actingAs($user)->post('/integrations/webhooks', [
            'url' => 'https://8.8.8.8/new',
            'is_enabled' => '1',
        ]);

        $this->assertDatabaseCount('webhooks', 1);
        $this->assertDatabaseHas('webhooks', ['user_id' => $user->id, 'url' => 'https://8.8.8.8/new']);
    }

    public function test_user_can_disable_the_webhook(): void
    {
        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://1.1.1.1/x', 'is_enabled' => true]);

        // Checkbox omitted = disabled.
        $this->actingAs($user)->post('/integrations/webhooks', ['url' => 'https://1.1.1.1/x']);

        $this->assertDatabaseHas('webhooks', ['user_id' => $user->id, 'is_enabled' => false]);
    }

    public function test_demo_user_cannot_save_a_webhook(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);

        $this->actingAs($demo)->post('/integrations/webhooks', [
            'url' => 'https://evil.example.test',
            'is_enabled' => '1',
        ])->assertRedirect();

        $this->assertDatabaseCount('webhooks', 0);
    }

    public function test_sending_a_test_delivers_to_the_url_and_logs_success(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://1.1.1.1/go', 'is_enabled' => true]);

        $this->actingAs($user)->post('/integrations/webhooks/test')
            ->assertRedirect(route('integrations.webhooks'));

        Http::assertSent(fn ($request) => $request->url() === 'https://1.1.1.1/go');

        $this->assertDatabaseHas('webhook_deliveries', [
            'event' => 'test',
            'successful' => true,
            'status_code' => 200,
        ]);
    }

    public function test_sending_a_test_logs_a_failed_delivery(): void
    {
        Http::fake(['*' => Http::response('server error', 500)]);

        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://1.1.1.1/bad', 'is_enabled' => true]);

        $this->actingAs($user)->post('/integrations/webhooks/test')->assertRedirect();

        $this->assertDatabaseHas('webhook_deliveries', [
            'event' => 'test',
            'successful' => false,
            'status_code' => 500,
        ]);
    }

    public function test_sending_a_test_records_connection_errors(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not connect'));

        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://1.1.1.1/down', 'is_enabled' => true]);

        $this->actingAs($user)->post('/integrations/webhooks/test')->assertRedirect();

        $delivery = $user->webhook->deliveries()->first();
        $this->assertNotNull($delivery);
        $this->assertFalse((bool) $delivery->successful);
        $this->assertNull($delivery->status_code);
        $this->assertNotNull($delivery->error);
    }

    public function test_cannot_send_a_test_when_disabled(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $user->webhook()->create(['url' => 'https://hooks.example.test/off', 'is_enabled' => false]);

        $this->actingAs($user)->post('/integrations/webhooks/test')
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseCount('webhook_deliveries', 0);
    }

    public function test_cannot_send_a_test_without_a_saved_webhook(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post('/integrations/webhooks/test')
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_demo_user_cannot_send_a_test(): void
    {
        Http::fake();

        $demo = User::factory()->create(['email' => config('demo.email')]);
        $demo->webhook()->create(['url' => 'https://hooks.example.test/demo', 'is_enabled' => true]);

        $this->actingAs($demo)->post('/integrations/webhooks/test')->assertRedirect();

        Http::assertNothingSent();
        $this->assertDatabaseCount('webhook_deliveries', 0);
    }

    public function test_delivery_log_only_shows_the_users_own_deliveries(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceHook = $alice->webhook()->create(['url' => 'https://alice.example.test', 'is_enabled' => true]);
        $aliceHook->deliveries()->create(['event' => 'test', 'successful' => true, 'status_code' => 200]);

        $bobHook = $bob->webhook()->create(['url' => 'https://bob.example.test', 'is_enabled' => true]);
        $bobHook->deliveries()->create(['event' => 'test', 'successful' => false, 'status_code' => 503]);

        $this->actingAs($alice)->get('/integrations/webhooks')
            ->assertOk()
            ->assertDontSee('503');
    }
}
