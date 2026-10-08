<?php

use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\UserPasswordResetController;
use App\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. パブリック トップページ (LP)
Route::get('/', function () {
    return view('welcome');
})->name('home');


// 2. アカウント新規登録（Signup）ルート
Route::prefix('signup')->group(function () {
    Route::get('/', [SignupController::class, 'index'])->name('signup');
    Route::post('/confirm', [SignupController::class, 'confirm'])->name('signup.confirm');
    Route::post('/register', [SignupController::class, 'register'])->name('signup.register');
});


// 3. ユーザー企業向けポータル（ログイン後共通ルート：auth:web 保護）
Route::middleware(['auth:web'])->group(function () {
    // ログアウト
    Route::match(['get', 'post'], '/logout', [UserAuthController::class, 'logout'])->name('logout');

    // ダッシュボード
    Route::get('/dashboard', function () {
        $user = Auth::guard('web')->user();
        return view('tenant.dashboard', [
            'currentCompany' => $user->company,
            'activeMenu'     => 'dashboard',
        ]);
    })->name('dashboard');

    // 各種設定ポータル（管理者のみ）
    Route::get('/settings', [\App\Http\Controllers\Tenant\SettingsController::class, 'index'])->name('tenant.settings');

    // マイプロフィール
    Route::prefix('profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\ProfileController::class, 'edit'])->name('tenant.profile');
        Route::post('/confirm', [\App\Http\Controllers\Tenant\ProfileController::class, 'confirm'])->name('tenant.profile.confirm');
        Route::post('/update', [\App\Http\Controllers\Tenant\ProfileController::class, 'update'])->name('tenant.profile.update');
        Route::get('/complete', [\App\Http\Controllers\Tenant\ProfileController::class, 'complete'])->name('tenant.profile.complete');
    });

    // 事務局へのお問い合わせ
    Route::prefix('contact')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\ContactController::class, 'index'])->name('tenant.contact');
        Route::post('/confirm', [\App\Http\Controllers\Tenant\ContactController::class, 'confirm'])->name('tenant.contact.confirm');
        Route::post('/send', [\App\Http\Controllers\Tenant\ContactController::class, 'send'])->name('tenant.contact.send');
        Route::get('/complete', [\App\Http\Controllers\Tenant\ContactController::class, 'complete'])->name('tenant.contact.complete');
    });
});


// グローバルURLパラメータ制約（システム予約ルートや事務局ルートとの競合回避）
Route::pattern('url_code', '^(?!(signup|dashboard|settings|profile|contact|logout|owntop|ownlogout|owncorp|ownnotice|owner|admin|up|api|own-.*)$)[a-zA-Z0-9_\-]+');

// 5. 企業別ログイン・パスワード再設定ルート（/{url_code}）
Route::prefix('{url_code}')->middleware(['tenant'])->group(function () {
    // 企業ログイン画面（/{url_code} および /{url_code}/login の両方に対応）
    Route::get('/', [UserAuthController::class, 'showLoginForm'])->name('tenant.entry');
    Route::post('/', [UserAuthController::class, 'login'])->name('tenant.entry.submit');
    Route::get('/login', [UserAuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/login', [UserAuthController::class, 'login'])->name('tenant.login.submit');

    // パスワード再設定（リマインダー）
    Route::get('/forgot-password', [UserPasswordResetController::class, 'showForgotForm'])->name('tenant.password.forgot');
    Route::post('/forgot-password', [UserPasswordResetController::class, 'sendResetLink'])->name('tenant.password.email');
    Route::get('/forgot-password/sent', [UserPasswordResetController::class, 'showSentView'])->name('tenant.password.sent');
    Route::get('/reset-password', [UserPasswordResetController::class, 'showResetForm'])->name('tenant.password.reset.form');
    Route::post('/reset-password', [UserPasswordResetController::class, 'resetPassword'])->name('tenant.password.update');
});

