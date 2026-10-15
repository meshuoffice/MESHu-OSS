@extends('layouts.tenant')

@section('title', 'お知らせ一覧 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item active" aria-current="page">お知らせ一覧</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">お知らせ一覧</h1>
    </div>
</div>

<!-- 検索 & フィルターエリア -->
<div class="card card-custom bg-white mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('tenant.notice.index') }}" class="row g-3 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="keyword" class="form-control border-start-0 ps-0" placeholder="キーワード・本文で検索..." value="{{ $keyword }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="sender_type" class="form-select form-select-sm">
                    <option value="">発信元: 全て</option>
                    <option value="admin" {{ $senderType === 'admin' ? 'selected' : '' }}>事務局発信</option>
                    <option value="company" {{ $senderType === 'company' ? 'selected' : '' }}>取引先・自社発信</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <div class="form-check pt-1">
                    <input class="form-check-input" type="checkbox" name="is_important" value="1" id="filter_important" {{ !empty($isImportant) ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="filter_important">
                        <span class="badge bg-danger ms-1">重要のみ</span>
                    </label>
                </div>
            </div>
            <div class="col-12 col-md-2 text-md-end">
                <a href="{{ route('tenant.notice.index') }}" class="btn btn-outline-secondary btn-sm me-1">クリア</a>
                <button type="submit" class="btn btn-dark btn-sm">検索する</button>
            </div>
        </form>
    </div>
</div>

<!-- お知らせ一覧リストカード -->
<div class="card card-custom bg-white shadow-sm mb-4 border-0">
    <div class="list-group list-group-flush">
        @if ($notices->isEmpty())
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                お知らせ情報は見つかりませんでした。
            </div>
        @else
            @foreach ($notices as $n)
                @php
                    $isImp = !empty($n->is_important);
                    $bgClass = $isImp ? 'bg-danger bg-opacity-10 border-start border-4 border-danger' : '';
                @endphp
                <div class="list-group-item p-4 {{ $bgClass }}">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            @if ($n->sender_type === 'admin')
                                <span class="badge bg-secondary-subtle text-secondary">事務局</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary">{{ $n->company->name ?? '取引先企業' }}</span>
                            @endif

                            @if ($isImp)
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.7rem;">
                                    <i class="bi bi-pin-angle-fill me-1"></i>重要
                                </span>
                            @endif
                        </div>
                        <small class="text-muted font-monospace">
                            <i class="bi bi-clock me-1"></i>{{ $n->published_at ? $n->published_at->format('Y/m/d H:i') : $n->created_at->format('Y/m/d H:i') }}
                        </small>
                    </div>

                    <h5 class="fw-bold mb-2">
                        <a href="{{ route('tenant.notice.show', ['id' => $n->id]) }}" class="text-decoration-none">
                            {{ $n->title }}
                        </a>
                    </h5>

                    <p class="text-muted small mb-3" style="line-height: 1.6;">
                        {{ \Illuminate\Support\Str::limit(strip_tags($n->content), 160) }}
                    </p>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top border-light">
                        <div>
                            @if ($n->attachments->isNotEmpty())
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-paperclip text-primary me-1"></i>{{ $n->attachments->first()->file_name }}
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-muted small">
                                <i class="bi bi-eye me-1"></i>閲覧数: {{ number_format($n->view_count ?? 0) }} 回
                            </span>
                            <a href="{{ route('tenant.notice.show', ['id' => $n->id]) }}" class="btn btn-outline-primary btn-sm">
                                <span>詳細を見る</span>
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- ページネーション -->
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-3 px-4">
        <span class="small text-muted">
            全 {{ number_format($notices->total()) }} 件中
            @if ($notices->total() > 0)
                {{ ($notices->currentPage() - 1) * $notices->perPage() + 1 }}-{{ min($notices->currentPage() * $notices->perPage(), $notices->total()) }} 件を表示
            @else
                0 件を表示
            @endif
        </span>
        @if ($notices->hasPages())
            {{ $notices->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>
@endsection
