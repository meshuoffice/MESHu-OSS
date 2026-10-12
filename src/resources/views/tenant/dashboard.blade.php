@extends('layouts.tenant')

@section('title', 'ダッシュボード | MESHu')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item active" aria-current="page">ダッシュボード</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">ダッシュボード</h1>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
        @if (($userRole ?? Auth::guard('web')->user()->role) === 'admin')
            <a href="{{ route('tenant.link.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 text-decoration-none">
                <i class="bi bi-share"></i>
                <span>企業とつながる</span>
            </a>
        @endif
        <a href="{{ url('/buy') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 text-decoration-none">
            <i class="bi bi-cart"></i>
            <span>見積・注文を行う</span>
        </a>
    </div>
</div>

<!-- サマリーカードエリア -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">つながり企業数 (MESH)</div>
                        <h2 class="fs-1 fw-bold mb-0">{{ $approvedLinkCount ?? 0 }}</h2>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill p-2 fs-6">
                        <i class="bi bi-share-fill"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    <span class="text-success"><i class="bi bi-check-circle"></i> 承認済み</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">購入（進行中）</div>
                        <h2 class="fs-1 fw-bold mb-0">0</h2>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-cart"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    見積回答待ち / 注文確認待ち
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">販売（未処理）</div>
                        <h2 class="fs-1 fw-bold mb-0">0</h2>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-receipt-cutoff"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    見積回答待ち / 受注処理待ち
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card card-custom h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">今月の取引総額</div>
                        <h2 class="fs-1 fw-bold mb-0">¥0</h2>
                    </div>
                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-currency-yen"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    確定取引合計
                </div>
            </div>
        </div>
    </div>
</div>

<!-- メインコンテンツグリッド -->
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card card-custom mb-4 bg-white">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">最近のつながり企業 (MESH)</h5>
                @if (($userRole ?? Auth::guard('web')->user()->role) === 'admin')
                    <a href="{{ route('tenant.link.index') }}" class="btn btn-link btn-sm text-decoration-none p-0">すべて表示</a>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="ps-3">取引先企業名</th>
                            <th scope="col">自社属性</th>
                            <th scope="col">ステータス</th>
                            <th scope="col" class="pe-3 text-end">アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($recentLinks))
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">つながり企業データがありません。</td>
                            </tr>
                        @else
                            @foreach ($recentLinks as $item)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2 flex-wrap">
                                            <span>{{ $item['partner_name'] }}</span>
                                            @if (!empty($item['is_authenticated']))
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 align-middle" style="font-size: 0.68rem;" title="MESHu認証企業">
                                                    <i class="bi bi-patch-check-fill text-primary me-1"></i>認証企業
                                                </span>
                                            @endif
                                        </div>
                                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $item['partner_address'] }}</div>
                                    </td>
                                    <td>
                                        @if ($item['my_role'] === 'buyer')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-cart-fill me-1"></i>自社：購入側(買)</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-receipt-cutoff me-1"></i>自社：販売側(売)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item['link_status'] === 'approved')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>承認済み</span>
                                        @elseif ($item['link_status'] === 'pending')
                                            @if ($item['my_role'] === 'seller')
                                                <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock-history me-1"></i>未承認</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-send me-1"></i>回答待ち</span>
                                            @endif
                                        @elseif ($item['link_status'] === 'denied')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>否認</span>
                                        @endif
                                    </td>
                                    <td class="pe-3 text-end">
                                        @if (($userRole ?? Auth::guard('web')->user()->role) === 'admin')
                                            <a href="{{ route('tenant.link.detail', ['link_id' => $item['link_id']]) }}" class="btn btn-outline-secondary btn-sm fw-semibold">詳細</a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <!-- プラットフォーム・つながり企業からのお知らせ (最新5件) -->
        <div class="card card-custom mb-4 bg-white">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill text-primary me-2"></i>お知らせ</h5>
                <a href="{{ route('tenant.notice.index') }}" class="btn btn-link btn-sm text-decoration-none p-0">すべて表示</a>
            </div>
            <div class="list-group list-group-flush">
                @if (empty($recentNotices) || $recentNotices->isEmpty())
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-inbox fs-3 d-block text-secondary mb-2"></i>
                        現在、新着お知らせはありません。
                    </div>
                @else
                    @foreach ($recentNotices as $noticeItem)
                        @php
                            $isImp = !empty($noticeItem->is_important);
                            $bgClass = $isImp ? 'bg-danger bg-opacity-10' : '';
                        @endphp
                        <a href="{{ route('tenant.notice.show', ['id' => $noticeItem->id]) }}" class="list-group-item list-group-item-action p-3 {{ $bgClass }}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    @if ($noticeItem->sender_type === 'admin')
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size: 0.7rem;">事務局</span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary-emphasis" style="font-size: 0.7rem;">{{ $noticeItem->company->name ?? '取引先' }}</span>
                                    @endif

                                    @if ($isImp)
                                        <span class="badge bg-danger ms-1" style="font-size: 0.68rem;">重要</span>
                                    @endif
                                </div>
                                <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                                    {{ $noticeItem->published_at ? $noticeItem->published_at->format('Y/m/d') : $noticeItem->created_at->format('Y/m/d') }}
                                </small>
                            </div>
                            <h6 class="fw-bold mb-1 text-truncate" style="font-size: 0.9rem;">
                                {{ $noticeItem->title }}
                            </h6>
                            <p class="text-muted small mb-0 text-truncate" style="font-size: 0.8rem;">
                                {{ strip_tags($noticeItem->content) }}
                            </p>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
