<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

        // The sanctum guard memoizes the user it resolved from the first
        // request's bearer token, and the bound request keeps that cached
        // user resolver. Without clearing both, a second token request in the
        // same test method reuses Alice's resolution instead of resolving
        // Bob's token. Each real HTTP request is a fresh bootstrap, so this is
        // a test-harness artifact, not production behaviour.
        $this->resetResolvedAuth();

        $bobToken = $bob->createToken('api-access')->plainTextToken;
        $this->withToken($bobToken)->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(3);
    }

    /**
     * Drop the resolved guard user and cached request so the next
     * token-authenticated request re-resolves its user from scratch.
     */
    private function resetResolvedAuth(): void
    {
        Auth::forgetGuards();
        $this->app->forgetInstance('request');
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
}
