<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FireSafetyController;
use App\Http\Controllers\TyphoonController;
use App\Http\Controllers\IncidentController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Main dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Subsystem dashboards
// Fire Safety Routes
Route::prefix('fire-safety')->group(function () {
    Route::get('/dashboard', [FireSafetyController::class, 'dashboard'])->name('fire-safety.dashboard');
    Route::get('/alarm-systems', [FireSafetyController::class, 'alarmSystems'])->name('fire-safety.alarm-systems');
    Route::get('/extinguishers', [FireSafetyController::class, 'extinguishers'])->name('fire-safety.extinguishers');
    Route::get('/buildings', [FireSafetyController::class, 'buildings'])->name('fire-safety.buildings');
    Route::get('/evacuation-plans', [FireSafetyController::class, 'evacuationPlans'])->name('fire-safety.evacuation-plans');
    Route::get('/settings', [FireSafetyController::class, 'settings'])->name('fire-safety.settings');
});

Route::prefix('typhoon')->group(function () {
    Route::get('/dashboard', [TyphoonController::class, 'dashboard'])->name('typhoon.dashboard');
    // Add other typhoon routes here
});

Route::prefix('incidents')->group(function () {
    Route::get('/dashboard', [IncidentController::class, 'dashboard'])->name('incidents.dashboard');
    // Add other incident routes here
});
