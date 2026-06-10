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
    | The fixed, read-only Sanctum token secret shown on the API Access page
    | for the demo account, so any visitor can call GET /api/leads. The full
    | Bearer value is "{tokenId}|{this secret}", where tokenId is the seeded
    | personal_access_tokens row id. This grants read-only access to the demo
    | account's leads only.
    |
    */

    'api_token' => env('DEMO_API_TOKEN', 'demoReadOnlyApiToken000000000000000000000'),

];
