@extends('layouts.tenant')

@section('title', 'MESHu - パスワード再発行確認')

@section('styles')
<style>
    .confirm-label { font-weight: 700; color: #555; }
    .confirm-value {
        padding: 0.375rem 0.75rem;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        min-height: 38px;
        display: flex;
        align-items: center;
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
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.index') }}" class="text-decoration-none">従業員管理</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.show', ['user' => $targetUser->id]) }}" class="text-decoration-none">従業員詳細</a></li>
                <li class="breadcrumb-item active" aria-current="page">再発行確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">パスワード再発行の確認</h1>
    </div>
</div>

<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        以下の送信先メールアドレス宛に新しい仮パスワードを発行・送信します。よろしければ「再発行を実行する」ボタンをクリックしてください。
    </div>
</div>

<!-- 確認内容表示 -->
<div class="card card-custom bg-white shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-key-fill me-2 text-warning"></i>再発行内容</h5>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">対象者</div>
            <div class="col-md-9"><div class="confirm-value fw-bold">{{ $targetUser->user_name }} 様</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">ログインID (メールアドレス)</div>
            <div class="col-md-9"><div class="confirm-value font-monospace">{{ $targetUser->email }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">仮パスワード送信先</div>
            <div class="col-md-9"><div class="confirm-value font-monospace text-primary fw-bold">{{ $sendEmail }}</div></div>
        </div>
    </div>
</div>

<!-- 実行フォーム -->
<form action="{{ route('tenant.users.send_password', ['user' => $targetUser->id]) }}" method="POST">
    @csrf
    <input type="hidden" name="email" value="{{ $sendEmail }}">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i>修正する
        </button>
        <button type="submit" class="btn btn-warning px-5 py-2 fw-bold text-dark">
            再発行を実行する<i class="bi bi-send-fill ms-2"></i>
        </button>
    </div>
</form>
@endsection
