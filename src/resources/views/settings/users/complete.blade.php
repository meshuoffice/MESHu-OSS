@extends('layouts.tenant')

@section('title', 'MESHu - 保存完了')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.users.index') }}" class="text-decoration-none">従業員管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">完了</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">保存完了</h1>
    </div>
</div>

<!-- 完了カード -->
<div class="card card-custom bg-white shadow-sm py-5">
    <div class="card-body text-center py-4">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem; display: inline-block; line-height: 1;"></i>
        </div>
        <h3 class="fw-bold text-dark mb-3">従業員アカウント情報を保存しました</h3>
        
        @if (!empty($successInfo))
            <div class="alert alert-info d-inline-block text-start mb-4 px-4 py-3">
                <i class="bi bi-info-circle-fill me-2"></i>{{ $successInfo }}
            </div>
        @else
            <p class="text-muted mb-5">アカウント情報の更新・登録が正常に完了いたしました。</p>
        @endif

        <div>
            <a href="{{ route('tenant.users.index') }}" class="btn btn-primary px-4 py-2 me-2">従業員一覧へ戻る</a>
            <a href="{{ route('tenant.settings') }}" class="btn btn-outline-secondary px-4 py-2">各種設定ポータルへ戻る</a>
        </div>
    </div>
</div>
@endsection
