<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Controllers\AdminAuthController;
use Modules\Admin\Controllers\AdminCompanyController;
use Modules\Admin\Controllers\AdminDashboardController;
use Modules\Admin\Controllers\AdminNoticeController;
use Modules\Admin\Controllers\AdminOwnerController;
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

    // 事務局アカウント管理 (owner)
    Route::prefix('owner')->group(function () {
        Route::get('/', [AdminOwnerController::class, 'index'])->name('admin.owner.index');
        Route::get('/index', [AdminOwnerController::class, 'index']);
        Route::get('/registration', [AdminOwnerController::class, 'registration'])->name('admin.owner.registration');
        Route::post('/confirm', [AdminOwnerController::class, 'confirm'])->name('admin.owner.confirm');
        Route::post('/complete', [AdminOwnerController::class, 'complete'])->name('admin.owner.complete');
        Route::get('/show/{id}', [AdminOwnerController::class, 'show'])->name('admin.owner.show');
        Route::get('/edit/{id}', [AdminOwnerController::class, 'edit'])->name('admin.owner.edit');
        Route::post('/update/{id}', [AdminOwnerController::class, 'update'])->name('admin.owner.update');
        Route::get('/delete/{id}', [AdminOwnerController::class, 'deleteConfirm'])->name('admin.owner.delete.confirm');
        Route::post('/delete/{id}', [AdminOwnerController::class, 'delete'])->name('admin.owner.delete');
    });

    // お知らせ管理 (ownnotice)
    Route::prefix('ownnotice')->group(function () {
        Route::get('/', [AdminNoticeController::class, 'index'])->name('admin.notice.index');
        Route::get('/index', [AdminNoticeController::class, 'index']);
        Route::get('/registration', [AdminNoticeController::class, 'registration'])->name('admin.notice.registration');
        Route::post('/confirm', [AdminNoticeController::class, 'confirm'])->name('admin.notice.confirm');
        Route::post('/complete', [AdminNoticeController::class, 'complete'])->name('admin.notice.complete');
        Route::get('/complete/{id}', [AdminNoticeController::class, 'completeShow'])->name('admin.notice.complete.show');
        Route::get('/show/{id}', [AdminNoticeController::class, 'show'])->name('admin.notice.show');
        Route::get('/edit/{id}', [AdminNoticeController::class, 'edit'])->name('admin.notice.edit');
        Route::post('/confirm-update/{id}', [AdminNoticeController::class, 'confirmUpdate'])->name('admin.notice.confirm_update');
        Route::post('/update/{id}', [AdminNoticeController::class, 'update'])->name('admin.notice.update');
        Route::get('/delete/{id}', [AdminNoticeController::class, 'deleteConfirm'])->name('admin.notice.delete.confirm');
        Route::post('/delete/{id}', [AdminNoticeController::class, 'delete'])->name('admin.notice.delete');
        Route::get('/attachment/{id}', [AdminNoticeController::class, 'downloadAttachment'])->name('admin.notice.download_attachment');
    });
});
