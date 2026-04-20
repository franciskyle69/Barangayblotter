<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForcedPasswordChangeController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TenantSelectController;
use App\Http\Controllers\BlotterRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MediationController;
use App\Http\Controllers\PatrolLogController;
use App\Http\Controllers\TenantBrandingController;
use App\Http\Controllers\TenantRolePermissionsController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\TenantUsersController;
use App\Http\Controllers\SuperTenantSignupRequestController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperRolePermissionsController;
use App\Http\Controllers\SuperActivityLogController;
use App\Http\Controllers\SuperBackupController;
use App\Http\Controllers\TenantSignupController;
use App\Http\Controllers\SystemUpdateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
    Route::get('tenant-signup', [TenantSignupController::class, 'create'])->name('tenant-signup.create');
    Route::post('tenant-signup', [TenantSignupController::class, 'store'])->name('tenant-signup.store');
    Route::get('tenant-signup/payment/success', [TenantSignupController::class, 'paymentSuccess'])->name('tenant-signup.payment.success');
    Route::get('tenant-signup/payment/cancel', [TenantSignupController::class, 'paymentCancel'])->name('tenant-signup.payment.cancel');
});

Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::put('password/force-change', [ForcedPasswordChangeController::class, 'update'])
    ->name('password.force.update')
    ->middleware('auth');

Route::middleware(['auth', 'password.change'])->group(function () {
    Route::get('tenant/select', [TenantSelectController::class, 'show'])->name('tenant.select');
    Route::post('tenant/select', [TenantSelectController::class, 'select'])->name('tenant.select.store');
});

// System update endpoints (city-level only)
Route::middleware(['auth', 'password.change', 'super_admin'])->group(function () {
    Route::post('/system/update', [SystemUpdateController::class, 'store'])
        ->middleware('can:trigger-system-update');
    Route::get('/system/update/{systemUpdate}', [SystemUpdateController::class, 'show'])
        ->middleware('can:trigger-system-update');
});

// Tenant-scoped app (barangay staff & residents)
Route::middleware(['auth', 'password.change', 'tenant', 'tenant.ensure', 'tenant.db'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('tenant.permission:manage_account_settings')->group(function () {
        Route::get('settings', [TenantSettingsController::class, 'index'])->name('settings.index');
        Route::put('settings/profile', [TenantSettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::put('settings/password', [TenantSettingsController::class, 'updatePassword'])->name('settings.password.update');
    });

    Route::middleware('tenant.permission:view_incidents')->group(function () {
        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    });

    Route::middleware('tenant.permission:create_incidents')->group(function () {
        Route::get('incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
        Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
    });

    Route::middleware('tenant.permission:request_blotter_copy')->group(function () {
        Route::get('blotter-requests/create', [BlotterRequestController::class, 'create'])->name('blotter-requests.create');
        Route::post('blotter-requests', [BlotterRequestController::class, 'store'])->name('blotter-requests.store');
    });

    Route::get('blotter-requests', [BlotterRequestController::class, 'index'])->name('blotter-requests.index');

    Route::middleware('tenant.permission:manage_branding')->group(function () {
        Route::get('branding', [TenantBrandingController::class, 'edit'])->name('branding.edit');
        Route::post('branding', [TenantBrandingController::class, 'update'])->name('branding.update');
    });

    Route::middleware('tenant.permission:manage_users')->group(function () {
        Route::get('users', [TenantUsersController::class, 'index'])->name('users.index');
        Route::post('users', [TenantUsersController::class, 'addTenantUser'])->name('users.store');
        Route::post('users/create-account', [TenantUsersController::class, 'createTenantUser'])->name('users.create-account');
        Route::put('users/{user}', [TenantUsersController::class, 'updateTenantUserRole'])->name('users.update');
        Route::delete('users/{user}', [TenantUsersController::class, 'removeTenantUser'])->name('users.destroy');
        Route::get('roles-permissions', [TenantRolePermissionsController::class, 'index'])->name('roles-permissions.index');
        Route::put('roles-permissions/{role}', [TenantRolePermissionsController::class, 'update'])->name('roles-permissions.update');
    });

    Route::middleware('tenant.permission:manage_incidents')->group(function () {
        Route::get('incidents/{incident}/edit', [IncidentController::class, 'edit'])->name('incidents.edit');
        Route::put('incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::delete('incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');
    });

    Route::middleware('tenant.permission:manage_mediations')->group(function () {
        Route::get('mediations', [MediationController::class, 'index'])->name('mediations.index');
        Route::get('incidents/{incident}/mediations/create', [MediationController::class, 'create'])->name('mediations.create');
        Route::post('mediations', [MediationController::class, 'store'])->name('mediations.store');
        Route::put('mediations/{mediation}', [MediationController::class, 'update'])->name('mediations.update');
    });

    Route::middleware('tenant.permission:manage_patrol_logs')->group(function () {
        Route::get('patrol', [PatrolLogController::class, 'index'])->name('patrol.index');
        Route::get('patrol/create', [PatrolLogController::class, 'create'])->name('patrol.create');
        Route::post('patrol', [PatrolLogController::class, 'store'])->name('patrol.store');
        Route::get('patrol/{patrol}/edit', [PatrolLogController::class, 'edit'])->name('patrol.edit');
        Route::put('patrol/{patrol}', [PatrolLogController::class, 'update'])->name('patrol.update');
    });

    Route::middleware('tenant.permission:review_blotter_requests')->group(function () {
        Route::post('blotter-requests/{blotterRequest}/approve', [BlotterRequestController::class, 'approve'])->name('blotter-requests.approve');
        Route::post('blotter-requests/{blotterRequest}/reject', [BlotterRequestController::class, 'reject'])->name('blotter-requests.reject');
    });
});

// Barangay super admin
Route::middleware(['auth', 'password.change', 'super_admin'])->prefix('super')->name('super.')->group(function () {
    Route::get('dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('settings', [SuperAdminController::class, 'settings'])->name('settings');
    Route::get('tenants', [SuperAdminController::class, 'tenants'])->name('tenants');
    Route::get('tenants/create', [SuperAdminController::class, 'createTenant'])->name('tenants.create');
    Route::post('tenants', [SuperAdminController::class, 'storeTenant'])->name('tenants.store');
    Route::get('tenants/{tenant}/edit', [SuperAdminController::class, 'editTenant'])->name('tenants.edit');
    Route::put('tenants/{tenant}', [SuperAdminController::class, 'updateTenant'])->name('tenants.update');
    Route::delete('tenants/{tenant}', [SuperAdminController::class, 'deleteTenant'])->name('tenants.destroy');
    Route::get('tenants/{tenant}/users', [SuperAdminController::class, 'tenantUsers'])->name('tenants.users');
    Route::post('tenants/{tenant}/users', [SuperAdminController::class, 'addTenantUser'])->name('tenants.users.store');
    Route::post('tenants/{tenant}/users/create-account', [SuperAdminController::class, 'createTenantUser'])->name('tenants.users.create-account');
    Route::put('tenants/{tenant}/users/{user}', [SuperAdminController::class, 'updateTenantUserRole'])->name('tenants.users.update');
    Route::delete('tenants/{tenant}/users/{user}', [SuperAdminController::class, 'removeTenantUser'])->name('tenants.users.destroy');
    Route::post('tenants/{tenant}/toggle', [SuperAdminController::class, 'toggleActive'])->name('tenants.toggle');
    Route::get('roles-permissions', [SuperRolePermissionsController::class, 'index'])->name('roles-permissions.index');
    Route::put('roles-permissions/{role}', [SuperRolePermissionsController::class, 'update'])->name('roles-permissions.update');
    Route::get('activity-logs', [SuperActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('tenant-signup-requests', [SuperTenantSignupRequestController::class, 'index'])->name('tenant-signup-requests.index');
    Route::post('tenant-signup-requests/{signupRequest}/approve', [SuperTenantSignupRequestController::class, 'approve'])->name('tenant-signup-requests.approve');
    Route::post('tenant-signup-requests/{signupRequest}/reject', [SuperTenantSignupRequestController::class, 'reject'])->name('tenant-signup-requests.reject');
    Route::get('backup-restore', [SuperBackupController::class, 'index'])->name('backup-restore.index');
    Route::post('backup-restore/create', [SuperBackupController::class, 'create'])->name('backup-restore.create');
    Route::get('backup-restore/download/{filename}', [SuperBackupController::class, 'download'])
        ->where('filename', '[A-Za-z0-9._-]+')
        ->name('backup-restore.download');
    Route::post('backup-restore/restore/{filename}', [SuperBackupController::class, 'restoreFromStored'])
        ->where('filename', '[A-Za-z0-9._-]+')
        ->name('backup-restore.restore');
    Route::post('backup-restore/restore-upload', [SuperBackupController::class, 'restoreFromUpload'])->name('backup-restore.restore-upload');
});
