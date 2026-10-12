@extends('layouts.tenant')

@section('title', 'MESHu - 各種設定')

@section('styles')
<style>
    .setting-card {
        transition: all 0.25s ease-in-out;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: 12px;
        text-decoration: none !important;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .setting-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        border-color: #6366f1;
    }
    .setting-card:hover .setting-icon-bg {
        background-color: #4f46e5 !important;
        color: #ffffff !important;
    }
    .setting-card:hover .setting-title {
        color: #4f46e5 !important;
    }
    .setting-icon-bg {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        transition: all 0.25s ease-in-out;
    }
    .section-header-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
        position: relative;
        padding-left: 12px;
    }
    .section-header-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 18px;
        background-color: #4f46e5;
        border-radius: 2px;
    }
</style>
@endsection

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item active" aria-current="page">各種設定</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">各種設定</h1>
    </div>
</div>

<!-- 1. 企業・組織設定グループ -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h2 class="section-header-title mb-0">自社・組織設定</h2>
    </div>
    <div class="row g-4">
        <!-- 企業情報メンテナンス -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('tenant.company.edit', ['url_code' => $currentCompany->url_code]) }}" class="card setting-card bg-white p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="setting-icon-bg bg-primary-subtle text-primary">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h5 fw-bold setting-title mb-1">企業情報メンテナンス</h3>
                        <span class="badge bg-light text-dark border">自社マスター</span>
                    </div>
                </div>
                <p class="text-muted small mb-3 flex-grow-1">
                    会社名、代表連絡先、所在地（郵便番号・住所）、法人番号等の自社基本情報の閲覧・変更を行います。
                </p>
                <div class="d-flex align-items-center text-primary fw-semibold small mt-auto">
                    <span>設定を開く</span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </a>
        </div>

        <!-- 従業員管理（自社ユーザー管理） -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('tenant.users.index') }}" class="card setting-card bg-white p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="setting-icon-bg bg-info-subtle text-info-emphasis">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h5 fw-bold setting-title mb-1">従業員管理</h3>
                        <span class="badge bg-light text-dark border">ユーザー・権限</span>
                    </div>
                </div>
                <p class="text-muted small mb-3 flex-grow-1">
                    自社の社員アカウントの登録、編集、パスワード変更、アクセス権限（管理者／一般等）の設定管理を行います。
                </p>
                <div class="d-flex align-items-center text-primary fw-semibold small mt-auto">
                    <span>設定を開く</span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- 2. 運用・取引先連携設定グループ -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h2 class="section-header-title mb-0">運用・取引先連携設定</h2>
    </div>
    <div class="row g-4">
        <!-- 1. つながり管理 (MESH) -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('tenant.link.index') }}" class="card setting-card bg-white p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="setting-icon-bg bg-primary-subtle text-primary">
                        <i class="bi bi-share-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h5 fw-bold setting-title mb-1">つながり管理 (MESH)</h3>
                        <span class="badge bg-light text-dark border">取引先ネットワーク</span>
                    </div>
                </div>
                <p class="text-muted small mb-3 flex-grow-1">
                    接続中の取引先企業一覧の確認、新規接続申請の承認・管理、自社取引パラメータ等の管理を行います。
                </p>
                <div class="d-flex align-items-center text-primary fw-semibold small mt-auto">
                    <span>設定を開く</span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </a>
        </div>

        <!-- 2. 招待先管理 -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('tenant.invitation.index') }}" class="card setting-card bg-white p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="setting-icon-bg bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-envelope-paper-heart-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h5 fw-bold setting-title mb-1">招待先管理</h3>
                        <span class="badge bg-light text-dark border">新規開拓</span>
                    </div>
                </div>
                <p class="text-muted small mb-3 flex-grow-1">
                    MESHu未参加の取引先企業への参加招待メール送信、招待ステータスの確認および再送を行います。
                </p>
                <div class="d-flex align-items-center text-primary fw-semibold small mt-auto">
                    <span>設定を開く</span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </a>
        </div>

        <!-- 3. お知らせ配信管理 -->
        <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ route('tenant.notice.manage.index') }}" class="card setting-card bg-white p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="setting-icon-bg bg-success-subtle text-success">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="h5 fw-bold setting-title mb-1">お知らせ配信管理</h3>
                        <span class="badge bg-light text-dark border">アナウンス</span>
                    </div>
                </div>
                <p class="text-muted small mb-3 flex-grow-1">
                    接続済みのつながり企業（取引先）へ向けた自社からのお知らせ・最新情報の投稿および配信履歴管理を行います。
                </p>
                <div class="d-flex align-items-center text-primary fw-semibold small mt-auto">
                    <span>設定を開く</span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
