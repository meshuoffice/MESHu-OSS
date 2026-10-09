@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.notice.index') }}" class="text-decoration-none">お知らせ管理</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ詳細</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ詳細</h1>
    </div>
    @if (in_array(Auth::guard('admin')->user()->role, ['admin', 'reviewer'], true))
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="{{ route('admin.notice.edit', $notice->id) }}" class="btn btn-dark btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-pencil"></i>
                <span>編集する</span>
            </a>
            @if (!$notice->is_published)
                <a href="{{ route('admin.notice.delete.confirm', $notice->id) }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
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
    @endif
</div>

<!-- 詳細内容エリア -->
<div class="card card-custom bg-white mx-auto" style="max-width: 860px;">
    <div class="card-header bg-light py-3 border-0 d-flex justify-content-between align-items-center">
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
                <td class="font-monospace fw-bold">NT-{{ sprintf('%04d', $notice->id) }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">発信元区分</th>
                <td>
                    @if ($notice->sender_type === 'admin')
                        <span class="badge bg-secondary-subtle text-secondary">事務局発信</span>
                    @else
                        <span class="badge bg-primary-subtle text-primary">企業発信: {{ $notice->company->name ?? '企業' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">掲載開始日時</th>
                <td class="font-monospace">
                    {{ $notice->published_at ? $notice->published_at->format('Y-m-d H:i') : '-' }}
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">カテゴリ</th>
                <td>
                    @if ($notice->category === 'important')
                        <span class="badge bg-danger">重要</span>
                    @elseif ($notice->category === 'maintenance')
                        <span class="badge bg-warning text-dark">メンテナンス</span>
                    @else
                        <span class="badge bg-info text-dark">お知らせ</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">タイトル</th>
                <td class="fw-bold">{{ $notice->title }}</td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">開示対象範囲</th>
                <td>
                    @if ($notice->sender_type === 'company')
                        <span class="text-muted">連携承認企業のみ</span>
                    @elseif ($notice->target_scope === 'verified')
                        <span class="badge bg-primary-subtle text-primary">認証企業のみ</span>
                    @elseif ($notice->target_scope === 'unverified')
                        <span class="badge bg-secondary-subtle text-secondary">非認証企業のみ</span>
                    @else
                        全員
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">重要フラグ</th>
                <td>
                    @if ($notice->is_important)
                        <span class="text-danger fw-bold"><i class="bi bi-check-lg me-1"></i>重要指定あり（最上部固定表示）</span>
                    @else
                        <span class="text-muted">通常表示</span>
                    @endif
                </td>
            </tr>
            <tr>
                <th class="bg-light text-muted w-25 small fw-bold">作成者</th>
                <td>
                    @if ($notice->sender_type === 'admin')
                        {{ $notice->createdByAdmin->name ?? '事務局' }}
                    @else
                        {{ $notice->createdByUser->user_name ?? '企業担当者' }} ({{ $notice->company->name ?? '' }})
                    @endif
                </td>
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
                    {{ number_format($notice->view_count) }} <span class="fw-normal text-muted small">回</span>
                </td>
            </tr>
        </table>

        <h6 class="fw-bold text-secondary mb-2">本文</h6>
        <div class="p-3 bg-light rounded border mb-4" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6; min-height: 160px;">{{ $notice->content }}</div>

        @if ($notice->attachments->isNotEmpty())
            @php $attachment = $notice->attachments->first(); @endphp
            <!-- 添付ファイルダウンロードエリア -->
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
                    <a href="{{ route('admin.notice.download_attachment', $attachment->id) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                        <i class="bi bi-download"></i>
                        <span>ダウンロード</span>
                    </a>
                </div>
            </div>
        @endif

        <div class="border-top pt-4 text-center">
            <a href="{{ route('admin.notice.index') }}" class="btn btn-outline-secondary px-4">一覧に戻る</a>
        </div>
    </div>
</div>

@endsection
