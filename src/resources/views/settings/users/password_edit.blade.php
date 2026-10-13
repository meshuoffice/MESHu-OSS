@extends('layouts.tenant')

@section('title', 'MESHu - パスワードの再発行')

@section('styles')
<style>
    .readonly-value {
        padding: 0.5rem 0.75rem;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        font-weight: 700;
        color: #1e293b;
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
                <li class="breadcrumb-item active" aria-current="page">パスワードの再発行</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">パスワードの再発行</h1>
    </div>
</div>

<!-- エラー表示メッセージ -->
@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong>入力内容にエラーがあります。</strong> 下記の項目をご確認ください。
            <ul class="mb-0 mt-1 small">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- 入力フォーム -->
<div class="card card-custom bg-white shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-key-fill me-2 text-warning"></i>パスワード再発行手続き</h5>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                再発行処理を実行すると、システムでランダムな仮パスワードが自動発行され、指定されたメールアドレスへ通知されます。
            </div>
        </div>

        <form action="{{ route('tenant.users.confirm_password', ['user' => $targetUser->id]) }}" method="POST" novalidate>
            @csrf

            <!-- 対象者の名前（表示専用） -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-dark">対象従業員（名前）</label>
                <div class="readonly-value">
                    {{ $targetUser->user_name }}
                    <span class="text-muted fw-normal ms-2">({{ $targetUser->department ?? '-' }} {{ $targetUser->position ?? '' }})</span>
                </div>
            </div>

            <!-- 再発行後のパスワード送信先 -->
            <div class="mb-4">
                <label for="email" class="form-label fw-semibold text-dark">再発行後のパスワード送信先 <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email', $targetUser->email) }}" required placeholder="例：yamada@sample-next.co.jp">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text text-muted">仮パスワードの通知メールがこのアドレスに送信されます。</div>
            </div>

            <!-- 再発行後のパスワード送信先確認 -->
            <div class="mb-4">
                <label for="email_confirm" class="form-label fw-semibold text-dark">再発行後のパスワード送信先 (確認) <span class="badge bg-danger ms-1" style="font-size: 0.75rem;">必須</span></label>
                <input type="email" name="email_confirm" class="form-control @error('email_confirm') is-invalid @enderror" id="email_confirm" value="{{ old('email_confirm', $targetUser->email) }}" required placeholder="確認のため再入力してください">
                @error('email_confirm')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text text-muted">確認のため、上記と同じメールアドレスを再度入力してください。</div>
            </div>

            <!-- ボタンエリア -->
            <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
                <a href="{{ route('tenant.users.show', ['user' => $targetUser->id]) }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>キャンセル
                </a>
                <button type="submit" class="btn btn-warning px-5 fw-bold text-dark shadow-sm">
                    確認画面へ進む <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
