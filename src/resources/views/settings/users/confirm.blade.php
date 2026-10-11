@extends('layouts.tenant')

@section('title', 'MESHu - 登録・変更内容の確認')

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
                <li class="breadcrumb-item active" aria-current="page">入力確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">登録・変更内容の確認</h1>
    </div>
</div>

<div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
    <i class="bi bi-info-circle-fill fs-5"></i>
    <div>
        以下の内容で従業員アカウント情報を保存します。よろしければ「この内容で保存する」ボタンをクリックしてください。
    </div>
</div>

<!-- 確認内容表示 -->
<div class="card card-custom bg-white shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-check-fill me-2 text-primary"></i>従業員アカウント情報</h5>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">名前</div>
            <div class="col-md-9"><div class="confirm-value fw-bold">{{ $postData['user_name'] }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">名前カナ</div>
            <div class="col-md-9"><div class="confirm-value">{{ $postData['user_name_kana'] }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">ログインID (メールアドレス)</div>
            <div class="col-md-9"><div class="confirm-value font-monospace">{{ $postData['email'] }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">部署</div>
            <div class="col-md-9"><div class="confirm-value">{{ !empty($postData['department']) ? $postData['department'] : '-' }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">役職</div>
            <div class="col-md-9"><div class="confirm-value">{{ !empty($postData['position']) ? $postData['position'] : '-' }}</div></div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">アクセス権限</div>
            <div class="col-md-9">
                <div class="confirm-value">
                    @if ($postData['role'] === 'admin')
                        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle me-2">自社管理者</span>
                        <span>(各種設定ポータルおよびすべての機能が操作可能)</span>
                    @else
                        <span class="badge bg-light text-dark border me-2">一般ユーザー</span>
                        <span>(受発注・取引閲覧のみ)</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">アカウントステータス</div>
            <div class="col-md-9">
                <div class="confirm-value">
                    @if ($postData['status'] === 'active')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">有効</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">停止中</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="row align-items-center mb-3">
            <div class="col-md-3 confirm-label">担当区分</div>
            <div class="col-md-9">
                <div class="confirm-value">
                    @if ((int)($postData['is_all_assigned'] ?? 1) === 1)
                        <span class="badge bg-success-subtle text-success border border-success-subtle me-2">すべての取引先を担当</span>
                        <span>(自社が連携するすべての取引先企業と取引可能)</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2">個別担当分け</span>
                        <span>(割り当てられた特定の取引先企業とのみ取引可能)</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 実行フォーム -->
@php
    $actionUrl = $isModeCreate 
        ? route('tenant.users.store') 
        : route('tenant.users.update', ['user' => $targetUser->id]);
@endphp

<form action="{{ $actionUrl }}" method="POST">
    @csrf
    @foreach ($postData as $key => $val)
        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
    @endforeach

    <div class="d-flex justify-content-between align-items-center mb-5">
        <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4 py-2">
            <i class="bi bi-arrow-left me-1"></i>修正する
        </button>
        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
            この内容で保存する<i class="bi bi-check-lg ms-1"></i>
        </button>
    </div>
</form>
@endsection
