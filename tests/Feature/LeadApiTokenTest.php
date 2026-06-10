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
}
