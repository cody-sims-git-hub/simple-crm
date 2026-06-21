<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_requires_authentication(): void
    {
        $this->get('/integrations')->assertRedirect('/login');
    }

    public function test_landing_shows_the_three_integration_cards(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/integrations')
            ->assertOk()
            ->assertSee('Integrations')
            ->assertSee('API Access')
            ->assertSee('Data Export')
            ->assertSee('Webhooks');
    }

    public function test_landing_links_to_each_integration_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/integrations')
            ->assertOk()
            ->assertSee(route('integrations.api'), false)
            ->assertSee(route('integrations.export'), false)
            ->assertSee(route('integrations.webhooks'), false);
    }
}
