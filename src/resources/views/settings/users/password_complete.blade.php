@extends('layouts.tenant')

@section('title', 'MESHu - パスワード再発行完了')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.index') }}" class="text-decoration-none">従業員管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">再発行完了</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">パスワード再発行完了</h1>
    </div>
</div>

<!-- 完了表示 -->
<div class="card card-custom bg-white shadow-sm py-5">
    <div class="card-body text-center py-4">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem; display: inline-block; line-height: 1;"></i>
        </div>
        <h3 class="fw-bold text-dark mb-3">パスワードを再発行しました</h3>
        <p class="text-muted mb-2">
            指定されたメールアドレス（<strong class="font-monospace text-dark">{{ $sendEmail }}</strong>）へ通知を行いました。
        </p>
        
        @if (!empty($tempPassword))
            <div class="alert alert-info d-inline-block text-start mb-4 px-4 py-3">
                <i class="bi bi-key-fill me-2"></i>発行された仮パスワード: <strong class="font-monospace fs-5 text-danger">{{ $tempPassword }}</strong>
            </div>
        @endif

        <p class="text-muted small mb-5">対象者様へ仮パスワードでの初回ログインおよびマイプロフィールからのパスワード変更手続きをご案内ください。</p>
        <div>
            <a href="{{ route('tenant.users.show', ['user' => $targetUser->id]) }}" class="btn btn-primary px-4 py-2 me-2">対象従業員の詳細へ戻る</a>
            <a href="{{ route('tenant.users.index') }}" class="btn btn-outline-secondary px-4 py-2">従業員一覧へ戻る</a>
        </div>
    </div>
</div>
@endsection
