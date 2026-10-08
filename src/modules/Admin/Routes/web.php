<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Controllers\AdminAuthController;
use Modules\Admin\Controllers\AdminCompanyController;
use Modules\Admin\Controllers\AdminDashboardController;
use Modules\Admin\Middleware\AdminIpRestriction;

/*
|--------------------------------------------------------------------------
| Admin Module Web Routes
|--------------------------------------------------------------------------
*/

$adminUrlPath = env('ADMIN_URL_PATH', 'admin');

// 1. 事務局認証ルーティング（シークレットURL & IP制限）
Route::prefix($adminUrlPath)->middleware([AdminIpRestriction::class])->group(function () {
    Route::get('/', [AdminAuthController::class, 'showLoginForm'])->name('admin.login.root');
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/', [AdminAuthController::class, 'login'])->name('admin.login.root.submit');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::get('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout.post');
});

// 2. 事務局管理画面ルーティング（auth:admin 保護）
Route::middleware([AdminIpRestriction::class, 'auth:admin'])->group(function () {
    Route::get('/owntop', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/ownlogout', [AdminAuthController::class, 'logout'])->name('admin.ownlogout');

    // 企業管理 (owncorp)
    Route::prefix('owncorp')->group(function () {
        Route::get('/', [AdminCompanyController::class, 'index'])->name('admin.corp.index');
        Route::get('/index', [AdminCompanyController::class, 'index']);
        Route::get('/registration', [AdminCompanyController::class, 'registration'])->name('admin.corp.registration');
        Route::post('/confirm', [AdminCompanyController::class, 'confirm'])->name('admin.corp.confirm');
        Route::post('/complete', [AdminCompanyController::class, 'complete'])->name('admin.corp.complete');
        Route::get('/show/{id}', [AdminCompanyController::class, 'show'])->name('admin.corp.show');
        Route::get('/edit/{id}', [AdminCompanyController::class, 'edit'])->name('admin.corp.edit');
        Route::post('/update/{id}', [AdminCompanyController::class, 'update'])->name('admin.corp.update');
        Route::get('/delete/{id}', [AdminCompanyController::class, 'deleteConfirm'])->name('admin.corp.delete.confirm');
        Route::post('/delete/{id}', [AdminCompanyController::class, 'delete'])->name('admin.corp.delete');
        Route::post('/toggle-badge/{id}', [AdminCompanyController::class, 'toggleBadge'])->name('admin.corp.toggle_badge');
        Route::get('/impersonate/{id}', [AdminCompanyController::class, 'impersonate'])->name('admin.corp.impersonate');
    });
});
