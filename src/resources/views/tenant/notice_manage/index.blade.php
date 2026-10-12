@extends('layouts.tenant')

@section('title', 'お知らせ配信管理 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ配信管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ配信管理</h1>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
        <a href="{{ route('tenant.notice.manage.new') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-lg"></i>
            <span>新規お知らせ作成</span>
        </a>
    </div>
</div>

<!-- アナウンスバナー（配信仕様の明記） -->
<div class="alert alert-info d-flex align-items-center gap-3 mb-4 border-0 shadow-sm" role="alert">
    <i class="bi bi-info-circle-fill fs-4 flex-shrink-0 text-primary"></i>
        <div class="small">
            自社が発信したお知らせは、<strong>「つながり（MESHu）」で連携が承認されている取引先企業の全ユーザー</strong>のダッシュボードおよびお知らせ一覧に配信されます。
        </div>
    </div>

    <!-- 検索 & フィルターエリア -->
    <div class="card card-custom bg-white mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('tenant.notice.manage.index') }}" class="row g-3 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="keyword" class="form-control border-start-0 ps-0" placeholder="タイトル・本文で検索..." value="{{ $keyword ?? '' }}">
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">すべてのステータス</option>
                        <option value="published" {{ ($status ?? '') === 'published' ? 'selected' : '' }}>公開中</option>
                        <option value="draft" {{ ($status ?? '') === 'draft' ? 'selected' : '' }}>下書き</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 text-end">
                    <a href="{{ route('tenant.notice.manage.index') }}" class="btn btn-outline-secondary btn-sm me-1">クリア</a>
                    <button type="submit" class="btn btn-dark btn-sm">検索</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 一覧テーブル -->
    <div class="card card-custom bg-white border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>タイトル</th>
                            <th>添付</th>
                            <th>閲覧数</th>
                            <th>ステータス</th>
                            <th>掲載日時</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($notices->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">自社発信のお知らせはありません。</td>
                            </tr>
                        @else
                            @foreach ($notices as $n)
                                <tr>
                                    <td class="ps-4 font-monospace fw-bold small text-muted">
                                        NT-{{ str_pad((string)$n->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="fw-bold">
                                        <a href="{{ route('tenant.notice.manage.show', $n->id) }}" class="text-decoration-none">
                                            {{ $n->title }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($n->attachments->isNotEmpty())
                                            <i class="bi bi-paperclip text-primary fs-5" title="{{ $n->attachments->first()->file_name }}"></i>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace fw-semibold">
                                        {{ number_format((int)$n->view_count) }} <span class="fw-normal text-muted small">回</span>
                                    </td>
                                    <td>
                                        @if ($n->is_published)
                                            <span class="badge bg-success-subtle text-success">公開中</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">下書き</span>
                                        @endif
                                    </td>
                                    <td class="font-monospace small text-muted">
                                        {{ $n->published_at ? $n->published_at->format('Y-m-d H:i') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ページネーション -->
        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-3 px-4">
            <span class="small text-muted">全 {{ number_format($total) }} 件中 {{ $total > 0 ? (($page - 1) * $perPage + 1) . '-' . min($page * $perPage, $total) : '0' }} 件を表示</span>
            @if ($totalPages > 1)
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $notices->previousPageUrl() ?: '#' }}"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        @for ($p = 1; $p <= $totalPages; $p++)
                            <li class="page-item {{ $p === $page ? 'active' : '' }}">
                                <a class="page-link" href="{{ $notices->url($p) }}">{{ $p }}</a>
                            </li>
                        @endfor
                        <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $notices->nextPageUrl() ?: '#' }}"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
    </div>
@endsection
