<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a demo account with the starter CRM pipeline.
     */
    public function run(): void
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Agent',
                'password' => Hash::make('password'),
            ],
        );

        if ($demoUser->leads()->doesntExist()) {
            DemoData::provisionLeadsFor($demoUser);
        }

        // Fixed, read-only API token so demo visitors can call GET /api/leads.
        if ($demoUser->tokens()->where('name', 'api-access')->doesntExist()) {
            $demoUser->tokens()->create([
                'name' => 'api-access',
                'token' => hash('sha256', config('demo.api_token')),
                'abilities' => ['*'],
            ]);
        }
    }
}
