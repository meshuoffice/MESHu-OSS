@extends('layouts.tenant')

@section('title', $notice->title . ' | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ダッシュボード</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.notice.index') }}" class="text-decoration-none">お知らせ一覧</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ詳細</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ詳細</h1>
    </div>
</div>

<!-- 詳細カード -->
<div class="card card-custom bg-white border-0 shadow-sm mb-4">
    <div class="card-body p-4 p-md-5">
        
        <!-- ヘッダー情報 -->
        <div class="border-bottom pb-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    @if ($notice->sender_type === 'admin')
                        <span class="badge bg-secondary-subtle text-secondary">事務局発信</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary">発信企業: {{ $notice->company->name ?? '取引先企業' }}</span>
                    @endif

                    @if ($notice->is_important)
                        <span class="badge bg-danger">重要</span>
                    @endif
                </div>
                <div class="text-muted small font-monospace">
                    掲載日: {{ $notice->published_at ? $notice->published_at->format('Y/m/d H:i') : $notice->created_at->format('Y/m/d H:i') }}
                </div>
            </div>

            <h2 class="h4 fw-bold mb-3">{{ $notice->title }}</h2>

            <div class="d-flex justify-content-between align-items-center text-muted small flex-wrap gap-2">
                <div>
                    発信者: 
                    @if ($notice->sender_type === 'admin')
                        {{ $notice->createdByAdmin->name ?? 'MESHu事務局' }}
                    @else
                        {{ $notice->createdByUser->user_name ?? '企業担当者' }}
                    @endif
                </div>
                <div>
                    <i class="bi bi-eye me-1"></i>閲覧数: {{ number_format($notice->view_count ?? 0) }} 回
                </div>
            </div>
        </div>

        <!-- 本文 -->
        <div class="mb-5 font-monospace" style="white-space: pre-wrap; font-size: 1rem; line-height: 1.8; min-height: 200px; color: #1e293b;">{{ $notice->content }}</div>

        <!-- 添付ファイルエリア -->
        @if ($notice->attachments->isNotEmpty())
            <div class="card bg-light border mb-5">
                <div class="card-body p-3">
                    @foreach ($notice->attachments as $attachment)
                        <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2 pb-2 border-bottom' : '' }}">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-arrow-down text-primary fs-2"></i>
                                <div>
                                    <div class="fw-bold small">{{ $attachment->file_name }}</div>
                                    <div class="text-muted small">{{ number_format($attachment->file_size / 1024, 1) }} KB</div>
                                </div>
                            </div>
                            <a href="{{ route('tenant.notice.attachment', ['id' => $attachment->id]) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                                <i class="bi bi-download"></i>
                                <span>ダウンロード</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- フッター導線 -->
        <div class="border-top pt-4 text-center">
            <a href="{{ route('tenant.notice.index') }}" class="btn btn-outline-secondary px-4">
                <i class="bi bi-arrow-left me-1"></i>お知らせ一覧に戻る
            </a>
        </div>

    </div>
</div>
@endsection
