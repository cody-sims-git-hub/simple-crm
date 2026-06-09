<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Support\DemoData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_lead_stamps_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/leads', [
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
            'insurance_type' => 'Health',
            'notes' => 'Inbound web lead.',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'email' => 'jane@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_registration_provisions_the_starter_pipeline(): void
    {
        $this->post('/register', [
            'name' => 'New Agent',
            'email' => 'new.agent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'new.agent@example.com')->firstOrFail();

        $this->assertCount(count(DemoData::leads()), $user->leads()->get());
        $this->actingAs($user)->getJson('/api/leads')
            ->assertJsonCount(count(DemoData::leads()));
    }

    public function test_a_lead_can_be_created_without_optional_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/leads', [
            'name' => 'Minimal Lead',
            'email' => 'minimal@example.com',
            'insurance_type' => 'Life',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'email' => 'minimal@example.com',
            'phone' => null,
            'notes' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_unsupported_insurance_type_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/leads', [
            'name' => 'Bad Type',
            'email' => 'bad@example.com',
            'insurance_type' => 'Auto',
        ])->assertSessionHasErrors('insurance_type');

        $this->assertDatabaseMissing('leads', ['email' => 'bad@example.com']);
    }

    public function test_users_only_see_their_own_leads(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Lead::factory()->forUser($alice)->count(2)->create();
        Lead::factory()->forUser($bob)->count(1)->create();

        $this->actingAs($alice)->getJson('/api/leads')->assertJsonCount(2);
        $this->actingAs($bob)->getJson('/api/leads')->assertJsonCount(1);
    }

    public function test_a_user_cannot_view_another_users_lead(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceLead = Lead::factory()->forUser($alice)->create();

        $this->actingAs($bob)
            ->get("/leads/{$aliceLead->id}")
            ->assertNotFound();
    }

    public function test_a_user_cannot_delete_another_users_lead(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceLead = Lead::factory()->forUser($alice)->create();

        $this->actingAs($bob)
            ->delete("/leads/{$aliceLead->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('leads', ['id' => $aliceLead->id]);
    }

    public function test_the_leads_api_is_scoped_to_the_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Lead::factory()->forUser($alice)->count(2)->create();
        Lead::factory()->forUser($bob)->count(3)->create();

        $this->actingAs($alice)
            ->getJson('/api/leads')
            ->assertOk()
            ->assertJsonCount(2);
    }
}
