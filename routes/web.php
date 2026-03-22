<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TenantSelectController;
use App\Http\Controllers\BlotterRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MediationController;
use App\Http\Controllers\PatrolLogController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('tenant/select', [TenantSelectController::class, 'show'])->name('tenant.select');
    Route::post('tenant/select', [TenantSelectController::class, 'select'])->name('tenant.select.store');
});

// Tenant-scoped app (barangay staff & residents)
Route::middleware(['auth', 'tenant', 'tenant.ensure'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('incidents', IncidentController::class);

    Route::get('mediations', [MediationController::class, 'index'])->name('mediations.index');
    Route::get('incidents/{incident}/mediations/create', [MediationController::class, 'create'])->name('mediations.create');
    Route::post('mediations', [MediationController::class, 'store'])->name('mediations.store');
    Route::put('mediations/{mediation}', [MediationController::class, 'update'])->name('mediations.update');

    Route::get('patrol', [PatrolLogController::class, 'index'])->name('patrol.index');
    Route::get('patrol/create', [PatrolLogController::class, 'create'])->name('patrol.create');
    Route::post('patrol', [PatrolLogController::class, 'store'])->name('patrol.store');
    Route::get('patrol/{patrol}/edit', [PatrolLogController::class, 'edit'])->name('patrol.edit');
    Route::put('patrol/{patrol}', [PatrolLogController::class, 'update'])->name('patrol.update');

    Route::get('blotter-requests', [BlotterRequestController::class, 'index'])->name('blotter-requests.index');
    Route::get('blotter-requests/create', [BlotterRequestController::class, 'create'])->name('blotter-requests.create');
    Route::post('blotter-requests', [BlotterRequestController::class, 'store'])->name('blotter-requests.store');
    Route::post('blotter-requests/{blotterRequest}/approve', [BlotterRequestController::class, 'approve'])->name('blotter-requests.approve');
    Route::post('blotter-requests/{blotterRequest}/reject', [BlotterRequestController::class, 'reject'])->name('blotter-requests.reject');
});

// Barangay super admin
Route::middleware(['auth', 'super_admin'])->prefix('super')->name('super.')->group(function () {
    Route::get('dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('tenants', [SuperAdminController::class, 'tenants'])->name('tenants');
});
