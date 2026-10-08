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
        @if (Auth::guard('web')->user()->role === 'admin')
            <a href="{{ url('/link') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 text-decoration-none">
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
                        <h2 class="fs-1 fw-bold mb-0">{{ \App\Models\CompanyLink::where(function($q) use ($currentCompany) { $q->where('buyer_company_id', $currentCompany->id)->orWhere('seller_company_id', $currentCompany->id); })->where('status', 'approved')->count() }}</h2>
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
                @if (Auth::guard('web')->user()->role === 'admin')
                    <a href="{{ url('/link') }}" class="btn btn-link btn-sm text-decoration-none p-0">すべて表示</a>
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
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">つながり企業データがありません。</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card card-custom mb-4 bg-white">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-megaphone-fill text-primary me-2"></i>お知らせ</h5>
                <a href="{{ url('/notice') }}" class="btn btn-link btn-sm text-decoration-none p-0">すべて表示</a>
            </div>
            <div class="card-body p-3">
                <div class="text-center py-3 text-muted small">お知らせはありません。</div>
            </div>
        </div>
    </div>
</div>
@endsection
