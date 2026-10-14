@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '注文回答・受注承諾'))

@push('styles')
<style>
    .table-items th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .amount-summary-table td {
        padding: 0.4rem 0.75rem;
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
                <li class="breadcrumb-item"><a href="{{ url('/sell') }}" class="text-decoration-none">販売 (売手)</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/sell/detail/' . $tx['id']) }}" class="text-decoration-none">取引詳細 #{{ $tx['id'] }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">注文回答（受注承諾）の確認</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">注文回答（受注承諾）の確認</h1>
    </div>
</div>

<!-- アラート -->
<div class="alert alert-success d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill fs-4 text-success"></i>
    <div>
        買手企業（<strong>{{ $tx['partner_name'] }}</strong>）からの注文内容を受注承諾します。<br>
        承諾送信を行うと<strong>取引成立（完了）</strong>となり、本取引の明細・金額は確定（編集ロック）されます。
    </div>
</div>

<form method="post" action="{{ url('/sell/order-accept/' . $tx['id']) }}">
    @csrf

    <div class="row g-4">
        <div class="col-lg-12">
            <!-- 基本情報 -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>注文基本情報 & 確定納期</h5>
                </div>
                <div class="card-body p-4">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted">取引番号</dt>
                        <dd class="col-sm-9 font-monospace fw-bold">#{{ $tx['id'] }}</dd>

                        <dt class="col-sm-3 text-muted">注文件名</dt>
                        <dd class="col-sm-9 fw-bold fs-6">{{ $tx['title'] }}</dd>

                        <dt class="col-sm-3 text-muted">購入元企業 (買手)</dt>
                        <dd class="col-sm-9 fw-bold text-dark">{{ $tx['partner_name'] }}</dd>

                        <dt class="col-sm-3 text-muted">納品確定日</dt>
                        <dd class="col-sm-9 font-monospace fw-bold text-success">
                            {{ !empty($tx['delivery_date']) ? date('Y/m/d', strtotime($tx['delivery_date'])) : (!empty($tx['desired_delivery_date']) ? date('Y/m/d', strtotime($tx['desired_delivery_date'])) : '-') }}
                        </dd>

                        <dt class="col-sm-3 text-muted mt-2">承諾時メッセージ / 連絡事項</dt>
                        <dd class="col-sm-9 mt-2">
                            <textarea name="accept_remarks" class="form-control" rows="3" placeholder="受注承諾にあたっての買手への連絡事項や納品スケジュールを入力してください...">{{ $tx['seller_remarks'] ?? '' }}</textarea>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- 確定明細 & 金額確認 -->
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2 text-primary"></i>受注明細 & 確定金額</h5>
                    <span class="badge bg-secondary-subtle text-secondary font-monospace">明細 {{ count($items ?? []) }} 件</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-items align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;" class="text-center">No.</th>
                                    <th>品名・型番・仕様</th>
                                    <th style="width: 110px;" class="text-end">数量</th>
                                    <th style="width: 70px;" class="text-center">単位</th>
                                    <th style="width: 130px;" class="text-end">単価 (税抜)</th>
                                    <th style="width: 140px;" class="text-end">金額 (税抜)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $idx => $item)
                                    <tr>
                                        <td class="text-center font-monospace text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item['item_name'] }}</div>
                                            @if (!empty($item['specification']))
                                                <small class="text-muted">{{ $item['specification'] }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">{{ number_format((float)$item['quantity'], 2) }}</td>
                                        <td class="text-center">{{ $item['unit'] ?? '個' }}</td>
                                        <td class="text-end font-monospace">
                                            @if ($item['unit_price'] !== null)
                                                ¥{{ number_format((float)$item['unit_price'], 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold">
                                            @if ($item['amount'] !== null)
                                                ¥{{ number_format((float)$item['amount']) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- 金額サマリー -->
                    <div class="p-3 bg-light border-top d-flex justify-content-end">
                        <table class="amount-summary-table" style="width: 320px;">
                            <tr>
                                <td class="text-muted">送料 (+)</td>
                                <td class="text-end font-monospace">¥{{ number_format((float)($tx['shipping_fee'] ?? 0)) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">値引き (-)</td>
                                <td class="text-end font-monospace">¥{{ number_format((float)($tx['discount_amount'] ?? 0)) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="fw-bold">税抜小計</td>
                                <td class="text-end font-monospace fw-bold">¥{{ number_format((float)($tx['subtotal_amount'] ?? 0)) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">消費税 ({{ $tx['tax_rate'] ?? 10 }}%・{{ \App\Models\Transaction::getRoundingLabel($tx['tax_rounding'] ?? null) }})</td>
                                <td class="text-end font-monospace">¥{{ number_format((float)($tx['tax_amount'] ?? 0)) }}</td>
                            </tr>
                            <tr class="border-top border-2 border-dark">
                                <td class="fw-bold fs-5 text-dark">合計金額 (税込)</td>
                                <td class="text-end font-monospace fw-bold fs-4 text-success">¥{{ number_format((float)($tx['total_amount'] ?? 0)) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 送信ボタン -->
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ url('/sell/edit/' . $tx['id']) }}" class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>明細・金額の修正へ戻る
                </a>
                <button type="submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i>受注を正式承諾する (取引成立)
                </button>
            </div>
        </div>
    </div>
</form>
@endsection
