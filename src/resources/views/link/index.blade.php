@extends('layouts.tenant')

@section('title', 'MESHu - ' . $title)

@section('content')
<!-- パンくずリスト & ページタイトル -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.settings') }}" class="text-decoration-none">各種設定</a></li>
                <li class="breadcrumb-item active" aria-current="page">つながり管理</li>
            </ol>
        </nav>
        <h1 class="h3 m-0 fw-bold">つながり管理 (MESH)</h1>
    </div>
    <div>
        <a href="{{ route('tenant.link.search') }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm px-3 py-2 fw-semibold">
            <i class="bi bi-plus-lg fs-6"></i>
            <span>新しいつながり</span>
        </a>
    </div>
</div>

<!-- フィルタ・検索カード -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('tenant.link.index') }}" class="row g-3 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="keyword" class="form-control" placeholder="企業名・法人番号・所在地で検索..." value="{{ $filters['keyword'] ?? '' }}">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <select name="my_role" class="form-select">
                    <option value="">すべての自社属性（買/売）</option>
                    <option value="buyer" {{ ($filters['my_role'] ?? '') === 'buyer' ? 'selected' : '' }}>自社が「購入側（買）」</option>
                    <option value="seller" {{ ($filters['my_role'] ?? '') === 'seller' ? 'selected' : '' }}>自社が「販売側（売）」</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <select name="status" class="form-select">
                    <option value="">すべてのステータス</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>承認済み（連携中）</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>承認待ち（受信/申請中）</option>
                    <option value="denied" {{ ($filters['status'] ?? '') === 'denied' ? 'selected' : '' }}>否認</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-secondary">絞り込む</button>
            </div>
        </form>
    </div>
</div>

<!-- つながり企業一覧テーブル -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold fs-6">連携企業一覧 (全 {{ count($links) }} 件)</h5>
    </div>
    <div class="table-responsive" style="min-height: 360px;">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 75px;">
                        <span>ID</span>
                        @if (($sort ?? '') === 'last_order_desc')
                            <a href="{{ route('tenant.link.index', array_merge(request()->query(), ['sort' => null])) }}" class="text-decoration-none text-primary ms-1" title="ID順（元に戻す）">
                                <i class="bi bi-arrow-down"></i>
                            </a>
                        @endif
                    </th>
                    <th scope="col">取引先企業名</th>
                    <th scope="col">自社属性</th>
                    <th scope="col" style="width: 150px;">
                        <span>最終依頼日</span>
                        @if (($sort ?? '') !== 'last_order_desc')
                            <a href="{{ route('tenant.link.index', array_merge(request()->query(), ['sort' => 'last_order_desc'])) }}" class="text-decoration-none text-primary ms-1" title="最終依頼日の降順に並び替え">
                                <i class="bi bi-arrow-down"></i>
                            </a>
                        @endif
                    </th>
                    <th scope="col">ステータス</th>
                    <th scope="col" class="text-end" style="width: 140px;">操作</th>
                </tr>
            </thead>
            <tbody>
                @if (empty($links))
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">該当する連携企業が存在しません。</td>
                    </tr>
                @else
                    @foreach ($links as $item)
                        <tr class="{{ $item['link_status'] === 'pending' ? 'table-warning-subtle' : ($item['link_status'] === 'denied' ? 'table-light' : '') }}">
                            <td class="font-monospace text-muted">#{{ $item['link_id'] }}</td>
                            <td>
                                <div class="fw-bold text-dark d-flex align-items-center gap-2 flex-wrap">
                                    <span>{{ $item['partner_name'] }}</span>
                                    @if (!empty($item['is_authenticated']))
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 align-middle" style="font-size: 0.7rem;" title="事務局により実在が認定されたMESHu認証企業">
                                            <i class="bi bi-patch-check-fill text-primary me-1"></i>認証企業
                                        </span>
                                    @endif
                                </div>
                                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $item['partner_full_address'] ?? $item['partner_address'] }}</div>
                            </td>
                            <td>
                                @if ($item['my_role'] === 'buyer')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-cart-fill me-1"></i>自社：購入側(買)</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-receipt-cutoff me-1"></i>自社：販売側(売)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $item['last_order_badge'] }} {{ $item['last_order_badge'] === 'warning' ? 'text-dark' : 'text-white' }} font-monospace px-2 py-1">
                                    {{ $item['last_order_date_formatted'] }}
                                </span>
                            </td>
                            <td>
                                @if ($item['link_status'] === 'approved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>承認済み</span>
                                @elseif ($item['link_status'] === 'pending')
                                    @if ($item['my_role'] === 'seller')
                                        <span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-clock-history me-1"></i>申請受信（未承認）</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-send me-1"></i>申請送信済み（回答待ち）</span>
                                    @endif
                                @elseif ($item['link_status'] === 'denied')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>否認（取引停止中）</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">メニュー</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="{{ route('tenant.link.detail', ['link_id' => $item['link_id']]) }}"><i class="bi bi-eye me-2 text-muted"></i>企業詳細を見る</a></li>
                                        
                                        <!-- 販売側のみステータス変更可能 (ルール: 販売側が承認/否認を操作) -->
                                        @if ($item['my_role'] === 'seller')
                                            <li><hr class="dropdown-divider"></li>
                                            @if ($item['link_status'] !== 'approved')
                                                <li>
                                                    <button type="button" class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#approveLinkModal_{{ $item['link_id'] }}">
                                                        <i class="bi bi-check-circle me-2"></i>連携を承認...
                                                    </button>
                                                </li>
                                            @endif
                                            @if ($item['link_status'] !== 'denied')
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#denyLinkModal_{{ $item['link_id'] }}">
                                                        <i class="bi bi-x-circle me-2"></i>連携を否認・停止...
                                                    </button>
                                                </li>
                                            @endif
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <!-- ページネーションフッター -->
    @php
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $startItem  = $totalCount > 0 ? (($page - 1) * $perPage + 1) : 0;
        $endItem    = min($page * $perPage, $totalCount);
        $queryParams = request()->query();
    @endphp
    <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">
            {{ $startItem }} - {{ $endItem }} 件 / 全 {{ $totalCount }} 件
        </div>
        @if ($totalPages > 1)
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <!-- 前へ -->
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        @php $queryParams['page'] = $page - 1; @endphp
                        <a class="page-link" href="{{ route('tenant.link.index', $queryParams) }}">&laquo; 前へ</a>
                    </li>

                    <!-- ページ番号 -->
                    @for ($p = 1; $p <= $totalPages; $p++)
                        @php $queryParams['page'] = $p; @endphp
                        <li class="page-item {{ $p === $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ route('tenant.link.index', $queryParams) }}">{{ $p }}</a>
                        </li>
                    @endfor

                    <!-- 次へ -->
                    <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                        @php $queryParams['page'] = $page + 1; @endphp
                        <a class="page-link" href="{{ route('tenant.link.index', $queryParams) }}">次へ &raquo;</a>
                    </li>
                </ul>
            </nav>
        @endif
    </div>
</div>

<!-- モーダル定義群 -->
@if (!empty($links))
    @foreach ($links as $item)
        @if ($item['my_role'] === 'seller' && $item['link_status'] !== 'approved')
            <div class="modal fade" id="approveLinkModal_{{ $item['link_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('tenant.link.status', ['link_id' => $item['link_id']]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <div class="modal-header border-bottom py-3">
                                <h5 class="modal-title h6 fw-bold m-0 text-success">
                                    <i class="bi bi-check-circle-fill me-2"></i>連携承認の確認
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="mb-3 text-dark">
                                    <strong>{{ $item['partner_name'] }}</strong> からの連携依頼を「承認」しますか？
                                </p>
                                <div class="p-3 bg-light rounded small text-muted mb-0">
                                    <i class="bi bi-info-circle me-1"></i>承認すると、相手企業（購入側）との相互の見積・注文取引が開始可能になります。
                                </div>
                            </div>
                            <div class="modal-footer border-top py-2 bg-light">
                                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">キャンセル</button>
                                <button type="submit" class="btn btn-success px-4 fw-semibold">
                                    <i class="bi bi-check-lg me-1"></i>承認を実行する
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if ($item['my_role'] === 'seller' && $item['link_status'] !== 'denied')
            <div class="modal fade" id="denyLinkModal_{{ $item['link_id'] }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('tenant.link.status', ['link_id' => $item['link_id']]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="denied">
                            <div class="modal-header border-bottom py-3">
                                <h5 class="modal-title h6 fw-bold m-0 text-danger">
                                    <i class="bi bi-x-circle-fill me-2"></i>連携停止・否認の確認
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="mb-3 text-dark">
                                    <strong>{{ $item['partner_name'] }}</strong> との連携を「否認（停止）」しますか？
                                </p>
                                <div class="p-3 bg-warning-subtle text-warning-emphasis rounded small mb-0">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>連携を停止すると、新規の受発注取引やデータの参照が一時遮断されます。（※いつでも再開可能です）
                                </div>
                            </div>
                            <div class="modal-footer border-top py-2 bg-light">
                                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">キャンセル</button>
                                <button type="submit" class="btn btn-danger px-4 fw-semibold">
                                    <i class="bi bi-x-lg me-1"></i>否認・停止を実行
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif
@endsection
