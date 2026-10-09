@extends('admin::layouts.admin')

@section('content')

@php
$isEdit = !empty($isEdit);
$targetUrl = $isEdit ? route('admin.notice.update', $noticeId) : route('admin.notice.complete');
$backUrl   = $isEdit ? route('admin.notice.edit', $noticeId) : route('admin.notice.registration');
$pageTitle = $isEdit ? 'お知らせ編集確認' : 'お知らせ登録確認';
@endphp

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.index') }}" class="text-decoration-none">お知らせ管理</a></li>
                @if ($isEdit)
                    <li class="breadcrumb-item"><a href="{{ route('admin.notice.show', $noticeId) }}" class="text-decoration-none">お知らせ詳細</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">{{ $pageTitle }}</h1>
    </div>
</div>

<!-- 確認内容エリア -->
<div class="card card-custom bg-white mx-auto" style="max-width: 860px;">
    <div class="card-header bg-light py-3 border-0">
        <h5 class="m-0 fw-bold text-center">以下の内容で{{ $isEdit ? '更新' : '登録' }}します。よろしいですか？</h5>
    </div>
    <div class="card-body p-4">
        
        <table class="table table-bordered align-middle mb-4">
            @if ($isEdit)
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">お知らせID</th>
                    <td class="font-monospace fw-bold">NT-{{ sprintf('%04d', $noticeId) }}</td>
                </tr>
            @endif
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">掲載開始日時</th>
                <td class="font-monospace">{{ date('Y-m-d H:i', strtotime($data['published_at'])) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">カテゴリ</th>
                <td>
                    @if ($data['category'] === 'important')
                        <span class="badge bg-danger">重要</span>
                    @elseif ($data['category'] === 'maintenance')
                        <span class="badge bg-warning text-dark">メンテナンス</span>
                    @else
                        <span class="badge bg-info text-dark">お知らせ</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">タイトル</th>
                <td class="fw-bold">{{ $data['title'] }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">開示対象範囲</th>
                <td>
                    @if ($data['target_scope'] === 'verified')
                        <span class="badge bg-primary-subtle text-primary">認証企業のみ</span>
                    @elseif ($data['target_scope'] === 'unverified')
                        <span class="badge bg-secondary-subtle text-secondary">非認証企業のみ</span>
                    @else
                        全員
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">重要フラグ</th>
                <td>
                    @if (!empty($data['is_important']))
                        <span class="text-danger fw-bold"><i class="bi bi-check-lg me-1"></i>重要指定あり（最上部固定表示）</span>
                    @else
                        <span class="text-muted">通常表示</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">公開状態</th>
                <td>
                    @if (!empty($data['is_published']))
                        <span class="badge bg-success-subtle text-success">公開</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">下書き（非公開）</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">添付ファイル</th>
                <td>
                    @if (!empty($data['attachment']))
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-arrow-down text-primary fs-5"></i>
                            <span class="fw-semibold">{{ $data['attachment']['file_name'] }}</span>
                            <span class="text-muted small">({{ number_format($data['attachment']['file_size'] / 1024, 1) }} KB)</span>
                            @if ($isEdit && !empty($data['is_new_attachment']))
                                <span class="badge bg-warning text-dark ms-2">新規差し替え</span>
                            @endif
                        </div>
                    @elseif ($isEdit && !empty($data['delete_attachment']))
                        <span class="text-danger fw-bold"><i class="bi bi-trash me-1"></i>添付ファイルを削除</span>
                    @else
                        <span class="text-muted">なし</span>
                    @endif
                </td>
            </tr>
        </table>

        <h6 class="fw-bold text-secondary mb-2">本文プレビュー</h6>
        <div class="p-3 bg-light rounded border mb-4" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $data['content'] }}</div>

        <form action="{{ $targetUrl }}" method="POST">
            @csrf
            <div class="d-flex justify-content-between border-top pt-4">
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>修正する</a>
                <button type="submit" class="btn btn-primary px-4">{{ $isEdit ? '更新を確定する' : '確定する' }} <i class="bi bi-check-lg ms-1"></i></button>
            </div>
        </form>
    </div>
</div>

@endsection
