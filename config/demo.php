<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Account
    |--------------------------------------------------------------------------
    |
    | The email address of the shared, read-only demo account. A user with
    | this email may browse the app but cannot create, update, or delete
    | records (enforced by the BlockDemoWrites middleware). Every other
    | registered user keeps full CRUD access to their own scoped data.
    |
    */

    'email' => env('DEMO_EMAIL', 'demo@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Demo API Token (secret half)
    |--------------------------------------------------------------------------
    |
    | The secret half of the demo account's fixed Sanctum token. The API Access
    | page displays the full Bearer value "{tokenId}|{this secret}" (tokenId is
    | the seeded personal_access_tokens row id; see DatabaseSeeder), so any
    | visitor can call GET /api/leads. Grants read-only access to the demo
    | account's leads only — the API exposes no write routes.
    |
    */

    'api_token' => env('DEMO_API_TOKEN', 'demoReadOnlyApiToken000000000000000000000'),

];
