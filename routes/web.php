<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

// --- Guest Authentication ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
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

});
