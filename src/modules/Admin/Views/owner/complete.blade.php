@extends('admin::layouts.admin')

@section('content')

@php $isEdit = !empty($isEdit); @endphp

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.owner.index') }}" class="text-decoration-none">事務局アカウント管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'アカウント情報更新完了' : 'アカウント登録完了' }}</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ $isEdit ? 'アカウント情報更新完了' : 'アカウント登録完了' }}</h1>
    </div>
</div>

<!-- 完了メッセージエリア -->
<div class="card card-custom bg-white max-width-600 mx-auto py-5">
    <div class="card-body text-center">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        </div>
        <h3 class="fw-bold mb-3">{{ $isEdit ? 'アカウント情報を更新しました' : '事務局アカウントの登録が完了しました' }}</h3>
        <p class="text-muted mb-4">
            「<strong>{{ $ownerName ?? '管理者' }}</strong>」様のアカウント情報が正常に{{ $isEdit ? '更新' : '登録' }}されました。
        </p>
        <div class="d-flex justify-content-center gap-2">
            @if ($isEdit && !empty($ownerId))
                <a href="{{ route('admin.owner.show', $ownerId) }}" class="btn btn-outline-secondary px-4 py-2">アカウント詳細へ戻る</a>
            @endif
            <a href="{{ route('admin.owner.index') }}" class="btn btn-dark px-4 py-2">アカウント一覧へ戻る</a>
        </div>
    </div>
</div>

@endsection
