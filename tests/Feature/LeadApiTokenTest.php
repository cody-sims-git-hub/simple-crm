<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/leads')->assertUnauthorized();
    }

    public function test_valid_token_returns_the_owners_leads(): void
    {
        $user = User::factory()->create();
        Lead::factory()->forUser($user)->count(2)->create();

        $token = $user->createToken('api-access')->plainTextToken;

        $this->withToken($token)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'name', 'status', 'insurance_type', 'lead_score']]);
    }

    public function test_token_only_returns_its_owners_leads(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Lead::factory()->forUser($alice)->count(2)->create();
        Lead::factory()->forUser($bob)->count(3)->create();

        $aliceToken = $alice->createToken('api-access')->plainTextToken;

        $this->withToken($aliceToken)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_a_users_token_never_returns_another_users_leads(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Lead::factory()->forUser($alice)->count(2)->create();
        Lead::factory()->forUser($bob)->count(3)->create();

        $bobToken = $bob->createToken('api-access')->plainTextToken;

        $this->withToken($bobToken)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_api_does_not_expose_sensitive_fields(): void
    {
        $user = User::factory()->create();
        Lead::factory()->forUser($user)->create([
            'email' => 'secret@example.com',
            'phone' => '555-0100',
            'notes' => 'private note',
        ]);

        $token = $user->createToken('api-access')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/leads')->assertOk();
        $response->assertJsonMissing(['email' => 'secret@example.com']);
        $response->assertJsonMissing(['phone' => '555-0100']);
        $response->assertJsonMissing(['notes' => 'private note']);
    }

    public function test_api_access_page_renders_for_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/api-access')
            ->assertOk()
            ->assertSee('GET /api/leads');
    }

    public function test_user_can_generate_an_api_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api-access/token')
            ->assertRedirect(route('api.show'))
            ->assertSessionHas('plain_text_token');

        $this->assertTrue(
            $user->fresh()->tokens()->where('name', 'api-access')->exists()
        );
    }

    public function test_regenerating_replaces_the_previous_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('api-access');

        $this->actingAs($user)->post('/api-access/token')->assertRedirect();

        $this->assertSame(
            1,
            $user->fresh()->tokens()->where('name', 'api-access')->count()
        );
    }

    public function test_demo_account_cannot_generate_a_token(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);

        $this->actingAs($demo)->post('/api-access/token')->assertRedirect();

        $this->assertFalse(
            $demo->fresh()->tokens()->where('name', 'api-access')->exists()
        );
    }
}
