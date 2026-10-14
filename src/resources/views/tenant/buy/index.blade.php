@extends('layouts.tenant')

@section('title', '購入 (買手) 取引一覧 | MESHu')

@section('content')
<!-- パンくずリスト & ページタイトル & 新規取引開始ボタン -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item active" aria-current="page">購入 (買手)</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">購入取引一覧</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ url('/buy/estimate/new') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2 shadow-sm px-3 py-2 fw-semibold">
            <i class="bi bi-file-earmark-text fs-6"></i>
            <span>見積</span>
        </a>
        <a href="{{ url('/buy/order/new') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm px-3 py-2 fw-semibold">
            <i class="bi bi-cart-plus fs-6"></i>
            <span>注文</span>
        </a>
    </div>
</div>

<!-- フィルタ・検索カード -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" action="{{ url('/buy') }}" class="row g-3 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="keyword" class="form-control" placeholder="件名・販売先企業名で検索..." value="{{ $filters['keyword'] ?? '' }}">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <select name="status" class="form-select">
                    <option value="">すべてのステータス</option>
                    <option value="estimate_request" {{ ($filters['status'] ?? '') === 'estimate_request' ? 'selected' : '' }}>見積 (回答待ち)</option>
                    <option value="estimate_replied" {{ ($filters['status'] ?? '') === 'estimate_replied' ? 'selected' : '' }}>見積回答 (発注待ち)</option>
                    <option value="estimate_declined" {{ ($filters['status'] ?? '') === 'estimate_declined' ? 'selected' : '' }}>見積辞退</option>
                    <option value="order_placed" {{ ($filters['status'] ?? '') === 'order_placed' ? 'selected' : '' }}>注文 (受注確認中)</option>
                    <option value="order_accepted" {{ ($filters['status'] ?? '') === 'order_accepted' ? 'selected' : '' }}>注文回答 (取引完了)</option>
                    <option value="order_declined" {{ ($filters['status'] ?? '') === 'order_declined' ? 'selected' : '' }}>注文辞退</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-grid d-md-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary w-100">絞り込む</button>
                @if (!empty($filters['keyword']) || !empty($filters['status']))
                    <a href="{{ url('/buy') }}" class="btn btn-outline-dark">クリア</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- 取引一覧テーブル -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold fs-6">購入取引一覧 (全 {{ $totalCount ?? count($list ?? []) }} 件)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 70px;">ID</th>
                    <th scope="col">販売先企業 (売手)</th>
                    <th scope="col">件名 / 案件名</th>
                    <th scope="col" style="width: 140px;">ステータス</th>
                    <th scope="col">希望納期 / 確定納期</th>
                    <th scope="col" class="text-end">合計金額 (税込)</th>
                    <th scope="col">起票日時</th>
                    <th scope="col" class="text-end" style="width: 120px;">操作</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($list))
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                            取引データがありません。
                        </td>
                    </tr>
                @else
                    @foreach ($list as $tx)
                        @php
                            $isDeclined = in_array($tx['status'], ['estimate_declined', 'order_declined'], true);
                        @endphp
                        <tr class="{{ $isDeclined ? 'table-light text-muted' : '' }}">
                            <td class="font-monospace text-muted">#{{ $tx['id'] }}</td>
                            <td>
                                <div class="fw-bold {{ $isDeclined ? 'text-secondary' : 'text-dark' }} d-flex align-items-center gap-1">
                                    {{ $tx['partner_name'] }}
                                    @if (!empty($tx['partner_badge_status']) && $tx['partner_badge_status'] === 'active')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">認証済</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ url('/buy/detail/' . $tx['id']) }}" class="fw-semibold text-decoration-none {{ $isDeclined ? 'text-secondary' : 'text-dark' }} d-block">
                                    {{ $tx['title'] }}
                                </a>
                                <div class="small text-muted d-flex gap-2">
                                    <span>明細 {{ (int)($tx['item_count'] ?? 0) }}件</span>
                                    @if ((int)($tx['attachment_count'] ?? 0) > 0)
                                        <span><i class="bi bi-paperclip"></i> 添付あり</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                {!! \App\Models\Transaction::getBadgeHtml($tx['status'], 'buyer') !!}
                            </td>
                            <td>
                                <div class="small">希望: {{ !empty($tx['desired_delivery_date']) ? date('Y/m/d', strtotime((string)$tx['desired_delivery_date'])) : '-' }}</div>
                                <div class="small {{ !empty($tx['delivery_date']) ? 'fw-bold text-success' : 'text-muted' }}">
                                    確定: {{ !empty($tx['delivery_date']) ? date('Y/m/d', strtotime((string)$tx['delivery_date'])) : '-' }}
                                </div>
                            </td>
                            <td class="text-end font-monospace">
                                @if ($tx['status'] === 'estimate_request')
                                    <span class="text-muted small">回答待ち</span>
                                @elseif ($tx['status'] === 'order_placed' && $tx['total_amount'] === null)
                                    <span class="text-muted small">確認中</span>
                                @elseif ($tx['total_amount'] !== null && !$isDeclined)
                                    <span class="fw-bold text-dark">¥{{ number_format((float)$tx['total_amount']) }}</span>
                                    @if ($tx['subtotal_amount'] !== null)
                                        <div class="small text-muted">(税抜 ¥{{ number_format((float)$tx['subtotal_amount']) }})</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ date('Y/m/d H:i', strtotime((string)$tx['created_at'])) }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config='{"strategy":"fixed"}'>
                                        メニュー
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item" href="{{ url('/buy/detail/' . $tx['id']) }}">
                                                <i class="bi bi-eye me-2 text-muted"></i>{{ $isDeclined ? '辞退理由・詳細を見る' : '取引詳細を見る' }}
                                            </a>
                                        </li>
                                        @if ($tx['status'] === 'estimate_replied')
                                            <li>
                                                <a class="dropdown-item text-primary fw-semibold" href="{{ url('/buy/order/estimate/' . $tx['id']) }}">
                                                    <i class="bi bi-cart-check me-2"></i>この見積で注文する
                                                </a>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item" href="{{ url('/buy/detail/' . $tx['id'] . '#comments') }}">
                                                <i class="bi bi-chat-dots me-2 text-muted"></i>取引コメント ({{ (int)($tx['comment_count'] ?? 0) }})
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <!-- ページネーション -->
    @if (($pageCount ?? 1) > 1)
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <div class="small text-muted">
                全 {{ $totalCount }} 件中 {{ (($page - 1) * $perPage) + 1 }} 〜 {{ min($page * $perPage, $totalCount) }} 件を表示
            </div>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('/buy?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) }}">前へ</a>
                    </li>
                    @for ($p = 1; $p <= $pageCount; $p++)
                        <li class="page-item {{ $p === $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ url('/buy?' . http_build_query(array_merge($filters, ['page' => $p]))) }}">{{ $p }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $pageCount ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ url('/buy?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) }}">次へ</a>
                    </li>
                </ul>
            </nav>
        </div>
    @endif
</div>
@endsection
