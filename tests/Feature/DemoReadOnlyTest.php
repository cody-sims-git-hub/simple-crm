<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function demoUser(): User
    {
        return User::factory()->create(['email' => config('demo.email')]);
    }

    public function test_demo_user_cannot_create_a_lead(): void
    {
        $demo = $this->demoUser();

        $this->actingAs($demo)->post('/leads', [
            'name' => 'Should Not Persist',
            'email' => 'nope@example.com',
            'insurance_type' => 'Health',
        ])->assertRedirect();

        $this->assertDatabaseMissing('leads', ['email' => 'nope@example.com']);
        $this->assertSame(0, Lead::withoutGlobalScopes()->count());
    }

    public function test_demo_user_cannot_update_a_lead(): void
    {
        $demo = $this->demoUser();
        $lead = Lead::factory()->forUser($demo)->create(['name' => 'Original Name']);

        $this->actingAs($demo)->put("/leads/{$lead->id}", [
            'name' => 'Tampered Name',
            'email' => $lead->email,
            'insurance_type' => 'Life',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'name' => 'Original Name']);
    }

    public function test_demo_user_cannot_change_a_lead_status(): void
    {
        $demo = $this->demoUser();
        $lead = Lead::factory()->forUser($demo)->create(['status' => 'New']);

        $this->actingAs($demo)->put("/leads/{$lead->id}/status", [
            'status' => 'Closed',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'New']);
    }

    public function test_demo_user_cannot_delete_a_lead(): void
    {
        $demo = $this->demoUser();
        $lead = Lead::factory()->forUser($demo)->create();

        $this->actingAs($demo)->delete("/leads/{$lead->id}")->assertRedirect();

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_demo_user_can_view_pages(): void
    {
        $demo = $this->demoUser();
        $lead = Lead::factory()->forUser($demo)->create();

        $this->actingAs($demo)->get('/')->assertOk();
        $this->actingAs($demo)->get('/leads')->assertOk();
        $this->actingAs($demo)->get('/reporting')->assertOk();
        $this->actingAs($demo)->get("/leads/{$lead->id}")->assertOk();
    }

    public function test_demo_user_can_still_log_out(): void
    {
        $demo = $this->demoUser();

        $this->actingAs($demo)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_demo_write_via_api_returns_403(): void
    {
        $demo = $this->demoUser();
        $lead = Lead::factory()->forUser($demo)->create();

        $this->actingAs($demo)
            ->deleteJson("/leads/{$lead->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_regular_user_retains_full_crud(): void
    {
        $user = User::factory()->create(); // not the demo email

        // Create
        $this->actingAs($user)->post('/leads', [
            'name' => 'Real Lead',
            'email' => 'real@example.com',
            'insurance_type' => 'Health',
        ])->assertRedirect();
        $this->assertDatabaseHas('leads', ['email' => 'real@example.com', 'user_id' => $user->id]);

        // Update + delete
        $lead = Lead::factory()->forUser($user)->create(['name' => 'Before']);
        $this->actingAs($user)->put("/leads/{$lead->id}", [
            'name' => 'After',
            'email' => $lead->email,
            'insurance_type' => 'Life',
        ])->assertRedirect();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'name' => 'After']);

        $this->actingAs($user)->delete("/leads/{$lead->id}")->assertRedirect();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }
}
