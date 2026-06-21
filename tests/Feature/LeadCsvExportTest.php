<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_authentication(): void
    {
        $this->get('/integrations/export')->assertRedirect('/login');
        $this->get('/integrations/export/leads.csv')->assertRedirect('/login');
    }

    public function test_export_page_links_to_the_csv_download(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/integrations/export')
            ->assertOk()
            ->assertSee('Data Export')
            ->assertSee(route('integrations.export.leads'), false);
    }

    public function test_csv_download_is_served_as_a_file(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/integrations/export/leads.csv')
            ->assertOk()
            ->assertDownload('leads.csv');
    }

    public function test_csv_contains_the_users_leads(): void
    {
        $user = User::factory()->create();
        Lead::factory()->forUser($user)->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $response = $this->actingAs($user)->get('/integrations/export/leads.csv')->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Name,Email', $content);
        $this->assertStringContainsString('Ada Lovelace', $content);
        $this->assertStringContainsString('ada@example.com', $content);
    }

    public function test_csv_excludes_other_users_leads(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Lead::factory()->forUser($alice)->create(['name' => 'Alice Lead']);
        Lead::factory()->forUser($bob)->create(['name' => 'Bob Lead']);

        $content = $this->actingAs($alice)
            ->get('/integrations/export/leads.csv')->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Alice Lead', $content);
        $this->assertStringNotContainsString('Bob Lead', $content);
    }

    public function test_demo_user_can_export(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        Lead::factory()->forUser($demo)->create(['name' => 'Demo Lead']);

        $content = $this->actingAs($demo)
            ->get('/integrations/export/leads.csv')->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Demo Lead', $content);
    }
}
