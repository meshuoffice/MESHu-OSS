@extends('layouts.tenant')

@section('title', 'MESHu - 企業情報メンテナンス（完了）')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定ポータル</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.company.edit', ['url_code' => $currentCompany->url_code]) }}" class="text-decoration-none">企業情報メンテナンス</a></li>
                <li class="breadcrumb-item active" aria-current="page">完了</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">メンテナンス完了</h1>
    </div>
</div>

<!-- 完了表示 -->
<div class="card card-custom bg-white shadow-sm py-5">
    <div class="card-body text-center py-4">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem; display: inline-block; line-height: 1;"></i>
        </div>
        <h3 class="fw-bold text-dark mb-3">企業基本情報を更新しました</h3>
        <p class="text-muted mb-5">変更内容はシステムに正しく保存されました。</p>
        <div>
            <a href="{{ route('tenant.company.edit', ['url_code' => $currentCompany->url_code]) }}" class="btn btn-primary px-4 py-2 me-2">企業情報メンテナンスへ戻る</a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary px-4 py-2">ダッシュボードへ戻る</a>
        </div>
    </div>
</div>
@endsection
