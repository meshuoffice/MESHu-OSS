@extends('layouts.tenant')

@section('title', 'MESHu - ' . $title)

@section('content')
<!-- パンくずリスト -->
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tenant.link.index') }}" class="text-decoration-none">つながり管理</a></li>
            <li class="breadcrumb-item active" aria-current="page">連携申請完了</li>
        </ol>
    </nav>
    <h1 class="h3 m-0 fw-bold">連携依頼 送信完了</h1>
</div>

<!-- 完了メッセージカード -->
<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        <div class="card border-0 shadow-sm text-center p-4 p-md-5">
            <div class="mb-4">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-check-circle-fill display-4"></i>
                </div>
            </div>

            <h2 class="h4 fw-bold text-dark mb-2">連携依頼を送信いたしました</h2>
            <p class="text-muted mb-4">
                相手企業の管理者宛へ承認依頼を送信いたしました。
            </p>

            <div class="alert alert-info border-0 text-start small mb-4">
                <i class="bi bi-info-circle-fill me-2"></i>
                相手企業がシステム上で連携依頼を「承認」すると、相互の見積・注文取引が開始可能になります。送信した申請は「つながり管理」一覧より随時ご確認いただけます。
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                <a href="{{ route('tenant.link.index') }}" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-list-ul me-2"></i>つながり管理一覧へ戻る
                </a>
                <a href="{{ route('tenant.link.search') }}" class="btn btn-outline-secondary px-4 py-2">
                    <i class="bi bi-search me-2"></i>続けて企業を検索する
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
