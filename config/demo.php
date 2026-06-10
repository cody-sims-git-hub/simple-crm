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

];
