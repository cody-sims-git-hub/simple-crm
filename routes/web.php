<?php

use App\Http\Controllers\ApiAccessController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Integrations\IntegrationsController;
use App\Http\Controllers\Integrations\WebhookController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

// --- Guest Authentication ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Password reset (self-service via emailed link)
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Authenticated CRM (demo account is read-only: write methods blocked) ---
Route::middleware(['auth', 'demo.readonly'])->group(function () {
    // Views & Pages
    Route::get('/', [LeadController::class, 'dashboard'])->name('dashboard');
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('/reporting', [LeadController::class, 'reporting'])->name('reporting');

    // Core CRM Form Actions
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::put('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.updateStatus');

    // Full Profile CRUD Update Route
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');

    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // --- Integrations area ---
    Route::get('/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');

    // API Access console + token generation (demo blocked from POST).
    Route::get('/integrations/api', [ApiAccessController::class, 'show'])->name('integrations.api');
    Route::post('/integrations/api/token', [ApiAccessController::class, 'regenerate'])->name('integrations.api.token');

    // CSV export (read-only; demo may export).
    Route::get('/integrations/export', [IntegrationsController::class, 'export'])->name('integrations.export');
    Route::get('/integrations/export/leads.csv', [IntegrationsController::class, 'downloadLeads'])->name('integrations.export.leads');

    // Webhooks: save URL, enable/disable, send a test (demo blocked from POST).
    Route::get('/integrations/webhooks', [WebhookController::class, 'index'])->name('integrations.webhooks');
    Route::post('/integrations/webhooks', [WebhookController::class, 'store'])->name('integrations.webhooks.store');
    Route::post('/integrations/webhooks/test', [WebhookController::class, 'test'])->name('integrations.webhooks.test');

});
