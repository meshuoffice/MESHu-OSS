@extends('layouts.tenant')

@section('title', 'MESHu - 従業員アカウントの削除確認')

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
                <li class="breadcrumb-item active" aria-current="page">削除確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold text-danger">従業員アカウントの削除</h1>
    </div>
</div>

<!-- 削除警告アラート -->
<div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div>
        <strong>【警告】以下の従業員アカウントを完全に削除します。</strong><br>
        物理削除を実行するとデータベースから完全に消去され、元に戻すことはできません。よろしければ「完全に削除する」ボタンをクリックしてください。
    </div>
</div>

<!-- 削除対象内容表示 -->
<div class="card card-custom bg-white shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-x-fill me-2 text-danger"></i>削除対象アカウント</h5>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">名前</div>
            <div class="col-md-9"><div class="confirm-value fw-bold">{{ $targetUser->user_name }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">名前カナ</div>
            <div class="col-md-9"><div class="confirm-value">{{ $targetUser->user_name_kana }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">ログインID (メールアドレス)</div>
            <div class="col-md-9"><div class="confirm-value font-monospace">{{ $targetUser->email }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">アカウントステータス</div>
            <div class="col-md-9">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">停止中</span>
            </div>
        </div>
    </div>
</div>

<!-- 削除実行フォーム -->
<form action="{{ route('tenant.users.destroy', ['user' => $targetUser->id]) }}" method="POST">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="{{ route('tenant.users.show', ['user' => $targetUser->id]) }}" class="btn btn-outline-secondary px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i>キャンセル
        </a>
        <button type="submit" class="btn btn-danger px-5 py-2 fw-bold">
            <i class="bi bi-trash me-1"></i>完全に削除する
        </button>
    </div>
</form>
@endsection
