@extends('admin::layouts.admin')

@section('content')

@php
$isEdit = !empty($isEdit);
$pageTitle = $isEdit ? 'お知らせ更新完了' : 'お知らせ登録完了';
$headingText = $isEdit ? 'お知らせの更新が完了しました' : 'お知らせの登録が完了しました';
$detailBtnText = $isEdit ? '更新内容を確認する' : '登録内容を確認する';
@endphp

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.index') }}" class="text-decoration-none">お知らせ管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ $pageTitle }}</h1>
    </div>
</div>

<!-- 完了メッセージエリア -->
<div class="card card-custom bg-white mx-auto text-center" style="max-width: 600px;">
    <div class="card-body p-5">
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        </div>
        <h4 class="fw-bold mb-3">{{ $headingText }}</h4>
        <p class="text-muted mb-4">
            お知らせ情報の保存が正常に完了しました。<br>
            公開設定されたお知らせは、指定された対象企業のポータル画面およびダッシュボードに反映されます。
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-secondary">お知らせ一覧に戻る</a>
            @if (!empty($noticeId))
                <a href="{{ route('admin.notice.show', $noticeId) }}" class="btn btn-primary">{{ $detailBtnText }}</a>
            @endif
        </div>
    </div>
</div>

@endsection
