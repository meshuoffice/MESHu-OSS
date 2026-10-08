@extends('admin::layouts.admin')

@php $isEdit = !empty($isEdit); @endphp

@section('title', $title ?? ($isEdit ? '企業情報更新完了 | MESHu 事務局管理' : '企業登録完了 | MESHu 事務局管理'))

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.corp.index') }}" class="text-decoration-none">企業管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? '企業情報更新完了' : '企業登録完了' }}</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ $isEdit ? '企業情報更新完了' : '企業登録完了' }}</h1>
    </div>
</div>

<!-- 完了メッセージエリア -->
<div class="card card-custom bg-white max-width-600 mx-auto py-5">
    <div class="card-body text-center">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        </div>
        <h3 class="fw-bold mb-3">{{ $isEdit ? '企業情報を更新しました' : '企業情報の登録が完了しました' }}</h3>
        <p class="text-muted mb-4">
            「<strong>{{ $companyName ?? '企業' }}</strong>」の企業情報が正常に{{ $isEdit ? '更新' : '登録' }}されました。
        </p>
        <div class="d-flex justify-content-center gap-2">
            @if ($isEdit && !empty($companyId))
                <a href="{{ route('admin.corp.show', ['id' => $companyId]) }}" class="btn btn-outline-secondary px-4 py-2">企業詳細へ戻る</a>
            @endif
            <a href="{{ route('admin.corp.index') }}" class="btn btn-dark px-4 py-2">企業一覧へ戻る</a>
        </div>
    </div>
</div>

@endsection
