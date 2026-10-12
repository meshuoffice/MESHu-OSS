@extends('layouts.tenant')

@section('title', 'お知らせ詳細 | MESHu')

@section('content')
<div class="container-fluid p-4">

    <!-- パンくずリスト & ページタイトル -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('/settings') }}" class="text-decoration-none">各種設定</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tenant.notice.manage.index') }}" class="text-decoration-none">お知らせ配信管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">お知らせ詳細</li>
                </ol>
            </nav>
            <h1 class="h3 m-0 fw-bold">お知らせ詳細</h1>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="{{ route('tenant.notice.manage.edit', $notice->id) }}" class="btn btn-dark btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-pencil"></i>
                <span>編集する</span>
            </a>
            @if (!$notice->is_published)
                <a href="{{ route('tenant.notice.manage.delete_confirm', $notice->id) }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-trash"></i>
                    <span>削除する</span>
                </a>
            @else
                <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" disabled title="公開中のお知らせは削除できません。下書きに変更してから削除してください。">
                    <i class="bi bi-trash"></i>
                    <span>削除不可 (公開中)</span>
                </button>
            @endif
        </div>
    </div>

    <!-- 詳細内容カード -->
    <div class="card card-custom bg-white mx-auto border-0 shadow-sm" style="max-width: 860px;">
        <div class="card-header bg-light py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold">掲載情報詳細</h5>
            @if ($notice->is_published)
                <span class="badge bg-success-subtle text-success">公開中</span>
            @else
                <span class="badge bg-secondary-subtle text-secondary">下書き</span>
            @endif
        </div>
        <div class="card-body p-4">
            
            <table class="table table-bordered align-middle mb-4">
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">お知らせID</th>
                    <td class="font-monospace fw-bold">NT-{{ str_pad((string)$notice->id, 4, '0', STR_PAD_LEFT) }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">配信先</th>
                    <td>
                        <span class="badge bg-primary-subtle text-primary">つながり承認企業（連携取引先）の全ユーザー</span>
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">掲載開始日時</th>
                    <td class="font-monospace">
                        {{ $notice->published_at ? $notice->published_at->format('Y-m-d H:i') : '-' }}
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">タイトル</th>
                    <td class="fw-bold fs-6">{{ $notice->title }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">作成者</th>
                    <td>{{ $notice->createdByUser ? $notice->createdByUser->user_name : '自社担当者' }}</td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">作成・更新日時</th>
                    <td class="font-monospace small text-muted">
                        登録: {{ $notice->created_at ? $notice->created_at->format('Y-m-d H:i:s') : '-' }} / 更新: {{ $notice->updated_at ? $notice->updated_at->format('Y-m-d H:i:s') : '-' }}
                    </td>
                </tr>
                <tr>
                    <th class="bg-light text-muted w-25 small fw-bold">閲覧数</th>
                    <td class="font-monospace fw-bold">
                        {{ number_format((int)$notice->view_count) }} <span class="fw-normal text-muted small">回</span>
                    </td>
                </tr>
            </table>

            <h6 class="fw-bold text-secondary mb-2">本文</h6>
            <div class="p-3 bg-light rounded border mb-4 font-monospace" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6; min-height: 160px;">{{ $notice->content }}</div>

            <!-- 添付ファイルエリア -->
            @if ($notice->attachments->isNotEmpty())
                @php $attachment = $notice->attachments->first(); @endphp
                <h6 class="fw-bold text-secondary mb-2">添付ファイル</h6>
                <div class="card bg-light border mb-4">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-file-earmark-arrow-down text-primary fs-3"></i>
                            <div>
                                <div class="fw-bold small">{{ $attachment->file_name }}</div>
                                <div class="text-muted small">{{ number_format($attachment->file_size / 1024, 1) }} KB</div>
                            </div>
                        </div>
                        <a href="{{ route('tenant.notice.attachment', $attachment->id) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                            <i class="bi bi-download"></i>
                            <span>ダウンロード</span>
                        </a>
                    </div>
                </div>
            @endif

            <div class="border-top pt-4 text-center">
                <a href="{{ route('tenant.notice.manage.index') }}" class="btn btn-outline-secondary px-4">一覧に戻る</a>
            </div>

        </div>
    </div>

</div>
@endsection
