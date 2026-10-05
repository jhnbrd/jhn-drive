<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\SecretController;
use App\Http\Controllers\ShareController;
use App\Http\Middleware\EnsureApproved;
use App\Http\Middleware\EnsureSuperadmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication & Access Request Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::get('/request-access', [AuthController::class, 'showRequestAccess'])->name('request-access');
    Route::post('/request-access', [AuthController::class, 'submitRequestAccess'])->middleware('throttle:access-request');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Protected Drive Routes (Requires Approved User Login)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureApproved::class])->group(function () {
    Route::get('/', [DriveController::class, 'index'])->name('drive.index');

    // Drive File Management API
    Route::prefix('api')->name('drive.')->group(function () {
        Route::get('/files', [DriveController::class, 'listFiles'])->name('files');
        Route::get('/home', [DriveController::class, 'home'])->name('home');
        Route::post('/upload', [DriveController::class, 'upload'])->name('upload');
        Route::post('/mkdir', [DriveController::class, 'mkdir'])->name('mkdir');
        Route::post('/download-selection', [DriveController::class, 'downloadSelection'])->name('download-selection');
        Route::delete('/delete', [DriveController::class, 'delete'])->name('delete');
        Route::post('/rename', [DriveController::class, 'rename'])->name('rename');
        Route::get('/download', [DriveController::class, 'download'])->name('download');
        Route::get('/preview', [DriveController::class, 'preview'])->name('preview');
        Route::post('/share', [DriveController::class, 'share'])->name('share');
        Route::post('/unshare', [DriveController::class, 'unshare'])->name('unshare');
        Route::get('/shared', [DriveController::class, 'sharedList'])->name('shared');
        Route::get('/stats', [DriveController::class, 'stats'])->name('stats');
    });

    // Superadmin Management Panel
    Route::prefix('admin')->name('admin.')->middleware(EnsureSuperadmin::class)->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/users/{id}/approve', [AdminController::class, 'approve'])->name('users.approve');
        Route::post('/users/{id}/reject', [AdminController::class, 'reject'])->name('users.reject');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
    });
});

/*
|--------------------------------------------------------------------------
| Public Share Routes (Unauthenticated, Token-Based)
|--------------------------------------------------------------------------
*/

Route::prefix('s')->name('share.')->group(function () {
    Route::get('/{token}', [ShareController::class, 'show'])->name('show')->middleware('throttle:public-share-view');
    Route::get('/{token}/preview', [ShareController::class, 'preview'])->name('preview')->middleware('throttle:public-share-view');
    Route::get('/{token}/download', [ShareController::class, 'download'])->name('download')->middleware('throttle:public-share-download');
    Route::get('/{token}/zip', [ShareController::class, 'downloadZip'])->name('zip')->middleware('throttle:public-share-download');
});

/*
|--------------------------------------------------------------------------
| Secret Vault Routes (PIN-gated, no login required)
|--------------------------------------------------------------------------
| These routes intentionally bypass the standard auth middleware.
| Access is controlled by PIN entry which sets a time-limited session.
| Never linked from the main app — direct URL access only.
*/

Route::get('/secret', [SecretController::class, 'showPin'])->name('secret.pin');
Route::post('/secret', [SecretController::class, 'verifyPin'])->name('secret.verify')->middleware('throttle:secret-pin');

Route::middleware('secret.vault.auth')->group(function () {
    Route::get('/secret/vault', [SecretController::class, 'showVault'])->name('secret.vault');
    Route::get('/secret/preview', [SecretController::class, 'preview'])->name('secret.preview');
    Route::get('/secret/thumbnail', [SecretController::class, 'thumbnail'])->name('secret.thumbnail');
    Route::get('/secret/download', [SecretController::class, 'download'])->name('secret.download');
    Route::post('/secret/upload', [SecretController::class, 'upload'])->name('secret.upload');
    Route::post('/secret/lock', [SecretController::class, 'lock'])->name('secret.lock');
});
