@extends('admin::layouts.admin')

@section('content')

<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">管理トップ</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ管理</h1>
    </div>
    @if (in_array(Auth::guard('admin')->user()->role, ['admin', 'reviewer'], true))
        <div class="mt-2 mt-sm-0">
            <a href="{{ route('admin.notice.registration') }}" class="btn btn-primary d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i>
                <span>新規お知らせ作成</span>
            </a>
        </div>
    @endif
</div>

<!-- 検索フィルター -->
<div class="card card-custom bg-white mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-search me-1"></i>検索条件</h6>
        <form action="{{ route('admin.notice.index') }}" method="GET" class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">キーワード（タイトル・本文）</label>
                <input type="text" name="keyword" class="form-control form-control-sm" placeholder="例: メンテナンス" value="{{ $keyword }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">カテゴリ</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="important" {{ $category === 'important' ? 'selected' : '' }}>重要</option>
                    <option value="maintenance" {{ $category === 'maintenance' ? 'selected' : '' }}>メンテナンス</option>
                    <option value="info" {{ $category === 'info' ? 'selected' : '' }}>お知らせ</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">公開対象</label>
                <select name="target_scope" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="all" {{ $targetScope === 'all' ? 'selected' : '' }}>全員</option>
                    <option value="verified" {{ $targetScope === 'verified' ? 'selected' : '' }}>認証企業のみ</option>
                    <option value="unverified" {{ $targetScope === 'unverified' ? 'selected' : '' }}>非認証企業のみ</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">ステータス</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="published" {{ $status === 'published' ? 'selected' : '' }}>公開中</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>下書き</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">発信元</label>
                <select name="sender_type" class="form-select form-select-sm">
                    <option value="">すべて</option>
                    <option value="admin" {{ $senderType === 'admin' ? 'selected' : '' }}>事務局</option>
                    <option value="company" {{ $senderType === 'company' ? 'selected' : '' }}>企業発信</option>
                </select>
            </div>
            <div class="col-12 text-end">
                <a href="{{ route('admin.notice.index') }}" class="btn btn-secondary btn-sm me-2"><i class="bi bi-arrow-counterclockwise me-1"></i>クリア</a>
                <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-search me-1"></i>検索する</button>
            </div>
        </form>
    </div>
</div>

<!-- 一覧テーブル -->
<div class="card card-custom bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>発信元</th>
                        <th>カテゴリ</th>
                        <th>タイトル</th>
                        <th>対象</th>
                        <th>添付</th>
                        <th>閲覧数</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($notices->isEmpty())
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">お知らせ情報が見つかりません。</td>
                        </tr>
                    @else
                        @foreach ($notices as $n)
                            <tr>
                                <td class="ps-4 font-monospace fw-bold small text-muted">
                                    NT-{{ sprintf('%04d', $n->id) }}
                                </td>
                                <td>
                                    @if ($n->sender_type === 'admin')
                                        <span class="badge bg-secondary-subtle text-secondary">事務局</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary">{{ $n->company->name ?? '企業' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($n->category === 'important')
                                        <span class="badge bg-danger">重要</span>
                                    @elseif ($n->category === 'maintenance')
                                        <span class="badge bg-warning text-dark">メンテナンス</span>
                                    @else
                                        <span class="badge bg-info text-dark">お知らせ</span>
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    <a href="{{ route('admin.notice.show', $n->id) }}" class="text-decoration-none">
                                        @if ($n->is_important)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1" style="font-size: 0.7rem;">固定</span>
                                        @endif
                                        {{ $n->title }}
                                    </a>
                                </td>
                                <td class="small">
                                    @if ($n->sender_type === 'company')
                                        <span class="text-muted">連携企業</span>
                                    @elseif ($n->target_scope === 'verified')
                                        <span class="badge bg-primary-subtle text-primary">認証企業</span>
                                    @elseif ($n->target_scope === 'unverified')
                                        <span class="badge bg-secondary-subtle text-secondary">非認証企業</span>
                                    @else
                                        全員
                                    @endif
                                </td>
                                <td>
                                    @if ($n->attachments->isNotEmpty())
                                        <i class="bi bi-paperclip text-primary" title="{{ $n->attachments->first()->file_name }}"></i>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="font-monospace">
                                    {{ number_format($n->view_count) }}
                                </td>
                                <td>
                                    @if ($n->is_published)
                                        <span class="badge bg-success-subtle text-success">公開中</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">下書き</span>
                                    @endif
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
        <span class="small text-muted">全 {{ number_format($notices->total()) }} 件中 {{ $notices->total() > 0 ? $notices->firstItem() . '-' . $notices->lastItem() : '0' }} 件を表示</span>
        @if ($notices->hasPages())
            {{ $notices->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>

@endsection
