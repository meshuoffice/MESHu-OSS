@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '注文内容の確認'))

@push('styles')
<style>
    .confirm-label {
        font-weight: 700;
        color: var(--color-text-muted, #555);
    }
    .confirm-value {
        padding: 0.5rem 0.75rem;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
    .table-items th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .file-item-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        background-color: #e9ecef;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 0.25rem;
    }
</style>
@endpush

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/buy') }}" class="text-decoration-none">購入 (買手)</a></li>
                <li class="breadcrumb-item active" aria-current="page">注文内容の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">注文内容の確認 <span class="badge bg-success-subtle text-success fs-6 fw-normal ms-2">発注内容確認</span></h1>
    </div>
</div>

<!-- アラートメッセージ -->
<div class="alert alert-success d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
    <i class="bi bi-cart-check-fill fs-4 text-success flex-shrink-0"></i>
    <div>
        以下の内容で販売企業（<strong>{{ $selectedPartner['partner_name'] ?? '発注先企業' }}</strong>）へ注文を送信します。<br>
        内容をご確認のうえ、「この内容で注文を送信する」ボタンをクリックしてください。（※販売企業が注文回答を行うことで取引成立となります）
    </div>
</div>

<!-- 確認フォーム -->
<form method="post" action="{{ url('/buy/order/create') }}" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="company_link_id" value="{{ $linkId }}">
    <input type="hidden" name="title" value="{{ $txData['title'] ?? '' }}">
    <input type="hidden" name="desired_delivery_date" value="{{ $txData['desired_delivery_date'] ?? '' }}">
    <input type="hidden" name="buyer_remarks" value="{{ $txData['buyer_remarks'] ?? '' }}">

    @foreach ($items as $idx => $item)
        <input type="hidden" name="items[{{ $idx }}][item_name]" value="{{ $item['item_name'] ?? '' }}">
        <input type="hidden" name="items[{{ $idx }}][specification]" value="{{ $item['specification'] ?? '' }}">
        <input type="hidden" name="items[{{ $idx }}][quantity]" value="{{ $item['quantity'] ?? 1 }}">
        <input type="hidden" name="items[{{ $idx }}][unit]" value="{{ $item['unit'] ?? '個' }}">
    @endforeach

    <!-- 1. 発注先企業 カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building-fill me-2 text-primary"></i>発注先企業 (売手)</h5>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">発注先企業名</div>
                <div class="col-md-9">
                    <div class="confirm-value fw-bold fs-6 text-dark">
                        {{ $selectedPartner['partner_name'] ?? '発注先企業' }}
                    </div>
                </div>
            </div>
            <div class="row align-items-center mb-0">
                <div class="col-md-3 confirm-label">所在地</div>
                <div class="col-md-9">
                    <div class="confirm-value text-dark">
                        <i class="bi bi-geo-alt me-1 text-muted"></i>{{ $selectedPartner['partner_full_address'] ?? ($selectedPartner['partner_address'] ?? '所在地未登録') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 基本情報 カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle-fill me-2 text-primary"></i>基本情報・希望納期</h5>
        </div>
        <div class="card-body p-4">
            <div class="row align-items-center mb-3">
                <div class="col-md-3 confirm-label">件名 / 発注案件名</div>
                <div class="col-md-9">
                    <div class="confirm-value fw-semibold text-dark">{{ $txData['title'] ?? '' }}</div>
                </div>
            </div>
            <div class="row align-items-center mb-0">
                <div class="col-md-3 confirm-label">希望納期</div>
                <div class="col-md-9">
                    <div class="confirm-value font-monospace fw-bold text-dark">
                        {{ !empty($txData['desired_delivery_date']) ? date('Y年m月d日', strtotime($txData['desired_delivery_date'])) : '指定なし' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. 注文明細 カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-cart-check-fill me-2 text-primary"></i>注文明細 ({{ count($items) }} 行)</h5>
            <div class="small text-muted">※単価・金額の確定は販売側企業が注文回答時に行います</div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-items align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">No.</th>
                            <th>品名・型番・仕様</th>
                            <th style="width: 140px;" class="text-end">発注数量</th>
                            <th style="width: 100px;" class="text-center">単位</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $idx => $item)
                            <tr>
                                <td class="text-center font-monospace text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item['item_name'] }}</div>
                                    @if (!empty($item['specification']))
                                        <div class="small text-muted">{{ $item['specification'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end font-monospace fw-bold">{{ number_format((float)$item['quantity'], 2) }}</td>
                                <td class="text-center">{{ $item['unit'] ?? '個' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. 添付ファイル カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-paperclip me-2 text-primary"></i>添付ファイル</h5>
        </div>
        <div class="card-body p-4">
            @if (!empty($uploadedFiles))
                <div class="d-flex flex-wrap align-items-center">
                    @foreach ($uploadedFiles as $uf)
                        @php
                            $sizeMB = number_format($uf['file_size'] / (1024 * 1024), 2);
                            $icon = 'bi-file-earmark-arrow-up-fill text-primary';
                            if (str_contains($uf['mime_type'], 'pdf')) {
                                $icon = 'bi-file-earmark-pdf-fill text-danger';
                            } elseif (str_contains($uf['mime_type'], 'image')) {
                                $icon = 'bi-file-earmark-image-fill text-success';
                            } elseif (str_ends_with(strtolower($uf['file_name']), '.zip') || str_ends_with(strtolower($uf['file_name']), '.rar')) {
                                $icon = 'bi-file-earmark-zip-fill text-warning';
                            }
                        @endphp
                        <div class="file-item-pill">
                            <i class="bi {{ $icon }}"></i>
                            <span class="fw-semibold">{{ $uf['file_name'] }}</span>
                            <span class="text-muted small">({{ $sizeMB }} MB)</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-muted small">添付ファイルはありません</div>
            @endif
        </div>
    </div>

    <!-- 5. 備考・特記事項 カード -->
    <div class="card card-custom bg-white shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-left-text-fill me-2 text-primary"></i>備考・特記事項</h5>
        </div>
        <div class="card-body p-4">
            @if (!empty($txData['buyer_remarks']))
                <div class="p-3 bg-light rounded border text-secondary" style="white-space: pre-wrap;">{{ $txData['buyer_remarks'] }}</div>
            @else
                <div class="text-muted small">特記事項なし</div>
            @endif
        </div>
    </div>

    <!-- 画面下部ボタン -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="window.history.back();">
            <i class="bi bi-arrow-left me-1"></i>修正する
        </button>
        <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
            この内容で注文を送信する <i class="bi bi-send-fill ms-1"></i>
        </button>
    </div>
</form>
@endsection
