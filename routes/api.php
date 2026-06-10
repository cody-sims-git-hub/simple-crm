<?php

use App\Http\Controllers\Api\LeadApiController;
use Illuminate\Support\Facades\Route;

// Token-authenticated, read-only leads API. Results are scoped to the
// authenticated token's owner inside the controller (see LeadApiController),
// because the model's global owner scope keys off the web-session guard and is
// a no-op under stateless Bearer-token auth.
Route::middleware('auth:sanctum')->get('/leads', [LeadApiController::class, 'index']);
