<?php

use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\UserPasswordResetController;
use App\Http\Controllers\SignupController;
use App\Models\Company;
use App\Models\CompanyLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    // ダッシュボード (mshdev 完全踏襲: 最近のつながり企業ボックス・サマリー反映)
    Route::get('/dashboard', function () {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return redirect('/');
        }

        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        $companyId = $company->id;
        $isAllAssigned = ($user->role === 'admin' || (int)$user->is_all_assigned === 1);
        $assignedUserId = $isAllAssigned ? null : (int)$user->id;

        // 最近のつながり企業一覧（全ステータス最新5件・担当絞り込み）
        $query = DB::table('company_links as cl')
            ->select([
                'cl.id as link_id',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                'cl.status as link_status',
                'cl.created_at as link_created_at',
                'cl.updated_at as link_updated_at',
                DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN 'buyer' ELSE 'seller' END as my_role"),
                'partner.id as partner_company_id',
                'partner.name as partner_name',
                'partner.abbreviated_name as partner_abbreviated_name',
                'partner.corporate_number as partner_corporate_number',
                'partner.zip_code as partner_zip_code',
                'partner.prefecture_code as partner_prefecture_code',
                'partner.city as partner_city',
                'partner.address as partner_address',
                'partner.status as partner_company_status',
                'cb.status as partner_badge_status',
            ])
            ->join('companies as partner', function ($join) use ($companyId) {
                $join->on('partner.id', '=', DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN cl.seller_company_id ELSE cl.buyer_company_id END"));
            })
            ->leftJoin('company_badges as cb', 'cb.company_id', '=', 'partner.id')
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            })
            ->where('partner.status', 'active');

        // 個別担当絞り込み
        if ($assignedUserId !== null) {
            $query->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                  ->where('ual.user_id', $assignedUserId);
        }

        $rows = $query->orderByDesc('cl.updated_at')
                      ->orderByDesc('cl.id')
                      ->limit(5)
                      ->get();

        $recentLinks = [];
        foreach ($rows as $row) {
            $prefName = Company::PREFECTURES[(int)$row->partner_prefecture_code] ?? '';
            $address = trim($prefName . ($row->partner_city ?? '') . ($row->partner_address ?? ''));

            $recentLinks[] = [
                'link_id'            => (int)$row->link_id,
                'my_role'            => $row->my_role,
                'link_status'        => $row->link_status,
                'partner_company_id' => (int)$row->partner_company_id,
                'partner_name'       => $row->partner_name,
                'partner_address'    => $address ?: '-',
                'is_authenticated'   => ($row->partner_badge_status === 'active'),
            ];
        }

        // つながり企業数（承認済み件数のみをカウント・担当絞り込み）
        $countQuery = DB::table('company_links as cl')
            ->join('companies as partner', function ($join) use ($companyId) {
                $join->on('partner.id', '=', DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN cl.seller_company_id ELSE cl.buyer_company_id END"));
            })
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            })
            ->where('partner.status', 'active')
            ->where('cl.status', 'approved');

        if ($assignedUserId !== null) {
            $countQuery->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                       ->where('ual.user_id', $assignedUserId);
        }

        $approvedLinkCount = $countQuery->count();

        // 最新のお知らせ（上位5件：重要固定優先）を取得
        $recentNotices = \App\Models\Notice::with(['company'])
            ->forCompany($companyId, $company->isAuthenticated())
            ->orderByDesc('is_important')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('tenant.dashboard', [
            'currentCompany'    => $company,
            'activeMenu'        => 'dashboard',
            'user'              => $user,
            'userRole'          => $user->role,
            'recentLinks'       => $recentLinks,
            'approvedLinkCount' => $approvedLinkCount,
            'recentNotices'     => $recentNotices,
        ]);
    })->name('dashboard');

    // 各種設定ポータル（管理者のみ）
    Route::get('/settings', [\App\Http\Controllers\Tenant\SettingsController::class, 'index'])->name('tenant.settings');

    // 自社企業情報メンテナンス（/{url_code}/settings/company）
    Route::prefix('{url_code}/settings/company')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\CompanyController::class, 'edit'])->name('tenant.company.edit');
        Route::post('/confirm', [\App\Http\Controllers\Tenant\CompanyController::class, 'confirm'])->name('tenant.company.confirm');
        Route::post('/update', [\App\Http\Controllers\Tenant\CompanyController::class, 'update'])->name('tenant.company.update');
        Route::get('/complete', [\App\Http\Controllers\Tenant\CompanyController::class, 'complete'])->name('tenant.company.complete');
    });

    // 自社従業員管理（mshdev 準拠: /users）
    Route::prefix('users')->name('tenant.users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\UserController::class, 'index'])->name('index');
        Route::get('/new', [\App\Http\Controllers\Tenant\UserController::class, 'create'])->name('create');
        Route::post('/confirm-create', [\App\Http\Controllers\Tenant\UserController::class, 'confirmCreate'])->name('confirm_create');
        Route::post('/create', [\App\Http\Controllers\Tenant\UserController::class, 'store'])->name('store');
        Route::get('/complete', [\App\Http\Controllers\Tenant\UserController::class, 'complete'])->name('complete');

        Route::get('/show/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'show'])->name('show');
        Route::get('/edit/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'edit'])->name('edit');
        Route::post('/confirm-update/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'confirmUpdate'])->name('confirm_update');
        Route::post('/update/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'update'])->name('update');
        Route::post('/assign-links/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'assignLinks'])->name('assign_links');

        Route::get('/password/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'passwordEdit'])->name('password_edit');
        Route::post('/confirm-password/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'confirmPassword'])->name('confirm_password');
        Route::post('/send-password/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'sendPassword'])->name('send_password');

        Route::get('/delete-confirm/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'confirmDelete'])->name('delete_confirm');
        Route::post('/delete/{user}', [\App\Http\Controllers\Tenant\UserController::class, 'destroy'])->name('destroy');
    });


    // つながり管理 (MESH) (mshdev 準拠: /link)
    Route::prefix('link')->name('tenant.link.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'index'])->name('index');
        Route::get('/search', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'search'])->name('search');
        Route::get('/show/{target_company_id}', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'show'])->name('show');
        Route::post('/request', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'requestLink'])->name('request');
        Route::get('/request-complete', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'requestComplete'])->name('request_complete');
        Route::get('/detail/{link_id}', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'detail'])->name('detail');
        Route::post('/status/{link_id}', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'updateStatus'])->name('status');
        Route::post('/assign-users/{link_id}', [\App\Http\Controllers\Tenant\CompanyLinkController::class, 'assignUsers'])->name('assign_users');
    });

    Route::get('/settings/company', function () {
        $user = Auth::guard('web')->user();
        return redirect()->route('tenant.company.edit', ['url_code' => $user->company->url_code]);
    });
    Route::get('/company', function () {
        $user = Auth::guard('web')->user();
        return redirect()->route('tenant.company.edit', ['url_code' => $user->company->url_code]);
    });

    // マイプロフィール
    Route::prefix('profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\ProfileController::class, 'edit'])->name('tenant.profile');
        Route::post('/confirm', [\App\Http\Controllers\Tenant\ProfileController::class, 'confirm'])->name('tenant.profile.confirm');
        Route::post('/update', [\App\Http\Controllers\Tenant\ProfileController::class, 'update'])->name('tenant.profile.update');
        Route::get('/complete', [\App\Http\Controllers\Tenant\ProfileController::class, 'complete'])->name('tenant.profile.complete');
    });

    // 招待先管理 (mshdev 準拠: /invitation)
    Route::prefix('invitation')->name('tenant.invitation.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\InvitationController::class, 'index'])->name('index');
        Route::get('/new', [\App\Http\Controllers\Tenant\InvitationController::class, 'new'])->name('new');
        Route::post('/confirm', [\App\Http\Controllers\Tenant\InvitationController::class, 'confirm'])->name('confirm');
        Route::post('/send', [\App\Http\Controllers\Tenant\InvitationController::class, 'send'])->name('send');
        Route::get('/complete', [\App\Http\Controllers\Tenant\InvitationController::class, 'complete'])->name('complete');
        Route::get('/show/{id}', [\App\Http\Controllers\Tenant\InvitationController::class, 'show'])->name('show');
        Route::get('/delete-confirm/{id}', [\App\Http\Controllers\Tenant\InvitationController::class, 'deleteConfirm'])->name('delete_confirm');
        Route::post('/delete/{id}', [\App\Http\Controllers\Tenant\InvitationController::class, 'destroy'])->name('delete');
    });

    // お知らせ配信管理 (mshdev 準拠: /notice/manage)
    Route::prefix('notice/manage')->name('tenant.notice.manage.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'index'])->name('index');
        Route::get('/show/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'show'])->name('show');
        Route::get('/new', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'new'])->name('new');
        Route::post('/confirm', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'confirm'])->name('confirm');
        Route::post('/create', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'create'])->name('create');
        Route::get('/edit/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'edit'])->name('edit');
        Route::post('/confirm-update/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'confirmUpdate'])->name('confirm_update');
        Route::post('/update/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'update'])->name('update');
        Route::get('/complete/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'complete'])->name('complete');
        Route::get('/delete/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'deleteConfirm'])->name('delete_confirm');
        Route::post('/delete/{id}', [\App\Http\Controllers\Tenant\NoticeManageController::class, 'destroy'])->name('delete');
    });

    // お知らせ (Notice) (mshdev 準拠: /notice)
    Route::prefix('notice')->name('tenant.notice.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Tenant\NoticeController::class, 'index'])->name('index');
        Route::get('/show/{id}', [\App\Http\Controllers\Tenant\NoticeController::class, 'show'])->name('show');
        Route::get('/{id}', [\App\Http\Controllers\Tenant\NoticeController::class, 'show'])->name('detail');
        Route::get('/attachment/{id}', [\App\Http\Controllers\Tenant\NoticeController::class, 'downloadAttachment'])->name('attachment');
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
Route::pattern('url_code', '^(?!(signup|dashboard|settings|users|link|invitation|notice|profile|contact|logout|owntop|ownlogout|owncorp|ownnotice|owner|admin|up|api|own-.*)$)[a-zA-Z0-9_\-]+');


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
