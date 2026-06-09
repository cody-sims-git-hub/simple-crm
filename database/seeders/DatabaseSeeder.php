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
    }
}
