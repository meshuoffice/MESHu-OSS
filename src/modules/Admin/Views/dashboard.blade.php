@extends('admin::layouts.admin')

@section('title', 'ダッシュボード | MESHu 事務局管理')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item active" aria-current="page">ダッシュボード</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">ダッシュボード</h1>
    </div>
</div>

<!-- 統計サマリーカードエリア -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">総登録企業数</div>
                        <h2 class="fs-1 fw-bold mb-0">{{ number_format($totalCompanies ?? \App\Models\Company::count()) }}</h2>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill p-2 fs-6">
                        <i class="bi bi-building"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    有効・停止中の企業合計
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">認証バッジ付与企業</div>
                        <h2 class="fs-1 fw-bold mb-0">{{ number_format($totalBadges ?? \App\Models\CompanyBadge::where('status', 'active')->count()) }}</h2>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-patch-check-fill text-warning"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    バッジ有効企業数
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">稼働中事務局アカウント</div>
                        <h2 class="fs-1 fw-bold mb-0">{{ number_format($totalOwners ?? \App\Models\Owner::where('status', 'active')->count()) }}</h2>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-people"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    アクティブな事務局ユーザー
                </div>
            </div>
        </div>
    </div>
</div>

<!-- クイックショートカット -->
<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold mb-3"><i class="bi bi-building me-2 text-primary"></i>企業管理</h5>
                <p class="card-text text-muted small flex-grow-1">MESHuに登録されている企業の検索、編集、削除、およびMESHu認証バッジの付与操作を行います。</p>
                <a href="{{ url('/owncorp') }}" class="btn btn-outline-primary mt-3">企業一覧へ</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold mb-3"><i class="bi bi-megaphone me-2 text-success"></i>お知らせ管理</h5>
                <p class="card-text text-muted small flex-grow-1">プラットフォーム全体または特定の企業グループに向けたお知らせ・重要通知・メンテナンス情報の作成と公開を行います。</p>
                <a href="{{ url('/ownnotice') }}" class="btn btn-outline-success mt-3">お知らせ一覧へ</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold mb-3"><i class="bi bi-shield-lock me-2 text-secondary"></i>アカウント管理</h5>
                <p class="card-text text-muted small flex-grow-1">事務局システムにログインできるメンバーの追加、および権限（最高管理者、審査担当者など）の設定を行います。</p>
                <a href="{{ url('/owner') }}" class="btn btn-outline-secondary mt-3">アカウント一覧へ</a>
            </div>
        </div>
    </div>
</div>
@endsection
