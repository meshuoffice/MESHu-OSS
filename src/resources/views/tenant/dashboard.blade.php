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
                        <h2 class="fs-1 fw-bold mb-0">{{ $buyerTxCounts['total'] ?? 0 }}</h2>
                    </div>
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-cart"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    見積回答待ち: {{ $buyerTxCounts['estimateRequest'] ?? 0 }}件 / 注文確認待ち: {{ $buyerTxCounts['estimateReplied'] ?? 0 }}件
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
                        <h2 class="fs-1 fw-bold mb-0">{{ $sellerTxCounts['total'] ?? 0 }}</h2>
                    </div>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-receipt-cutoff"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    見積回答待ち: {{ $sellerTxCounts['estimatePending'] ?? 0 }}件 / 受注処理待ち: {{ $sellerTxCounts['orderPending'] ?? 0 }}件
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
                        <h2 class="fs-1 fw-bold mb-0" title="{{ $monthlyTxTotal['exactTotal'] ?? '¥0' }}">{{ $monthlyTxTotal['formattedTotal'] ?? '¥0' }}</h2>
                    </div>
                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill p-2 fs-6">
                        <i class="bi bi-currency-yen"></i>
                    </span>
                </div>
                <div class="text-muted small">
                    @if (($monthlyTxTotal['growthRate'] ?? 0) >= 0)
                        前月比: <span class="text-success"><i class="bi bi-arrow-up-short"></i> +{{ $monthlyTxTotal['growthRate'] ?? 0 }}%</span>
                    @else
                        前月比: <span class="text-danger"><i class="bi bi-arrow-down-short"></i> {{ $monthlyTxTotal['growthRate'] ?? 0 }}%</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- メインコンテンツグリッド -->
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <!-- 最近のつながり企業 -->
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

        <!-- 最近の取引アクティビティ -->
        <div class="card card-custom bg-white">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">最近の取引アクティビティ</h5>
            </div>
            <div class="card-body">
                @if (empty($recentActivities))
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-clock-history fs-3 d-block text-muted mb-2"></i>
                        最近の取引アクティビティはありません。
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($recentActivities as $idx => $act)
                            @php
                                $isBuyer = ((int)$act['buyer_company_id'] === (int)$currentCompany->id);
                                $partnerName = $isBuyer ? ($act['seller_company_name'] ?? '相手企業') : ($act['buyer_company_name'] ?? '相手企業');
                                $detailUrl = $isBuyer ? url('buy/detail/' . $act['transaction_id']) : url('sell/detail/' . $act['transaction_id']);

                                $statusMeta = match ($act['history_status']) {
                                    'estimate_request' => $isBuyer
                                        ? ['title' => '見積依頼送信 (購入)', 'class' => 'text-primary', 'desc' => "{$partnerName} に対し見積依頼を送信しました。"]
                                        : ['title' => '見積依頼受信 (販売)', 'class' => 'text-info', 'desc' => "{$partnerName} より見積依頼を受信しました。"],
                                    'estimate_replied' => $isBuyer
                                        ? ['title' => '見積回答受信 (購入)', 'class' => 'text-success', 'desc' => "{$partnerName} より見積回答を受領しました。"]
                                        : ['title' => '見積回答送信 (販売)', 'class' => 'text-primary', 'desc' => "{$partnerName} に対し見積回答を送信しました。"],
                                    'estimate_declined' => $isBuyer
                                        ? ['title' => '見積辞退受領 (購入)', 'class' => 'text-secondary', 'desc' => "{$partnerName} より見積依頼の辞退連絡がありました。"]
                                        : ['title' => '見積辞退 (販売)', 'class' => 'text-secondary', 'desc' => "{$partnerName} からの見積依頼を辞退しました。"],
                                    'order_placed' => $isBuyer
                                        ? ['title' => '注文送信 (購入)', 'class' => 'text-primary', 'desc' => "{$partnerName} に対し注文を確定・送信しました。"]
                                        : ['title' => '新規注文受信 (販売)', 'class' => 'text-warning-emphasis', 'desc' => "{$partnerName} より注文を受信しました。"],
                                    'order_accepted' => $isBuyer
                                        ? ['title' => '注文承諾受領 (購入)', 'class' => 'text-success', 'desc' => "{$partnerName} が注文を承諾しました。"]
                                        : ['title' => '注文承諾 (販売)', 'class' => 'text-success', 'desc' => "{$partnerName} からの注文を承諾しました。"],
                                    'order_declined' => $isBuyer
                                        ? ['title' => '注文辞退受領 (購入)', 'class' => 'text-danger', 'desc' => "{$partnerName} より注文辞退の連絡がありました。"]
                                        : ['title' => '注文辞退 (販売)', 'class' => 'text-danger', 'desc' => "{$partnerName} からの注文を辞退しました。"],
                                    default => ['title' => '取引ステータス更新', 'class' => 'text-muted', 'desc' => "{$partnerName} との取引ステータスが更新されました。"],
                                };

                                $actTime = strtotime((string)$act['activity_at']);
                                $today = strtotime('today');
                                $yesterday = strtotime('yesterday');
                                if ($actTime >= $today) {
                                    $timeStr = '今日 ' . date('H:i', $actTime);
                                } elseif ($actTime >= $yesterday) {
                                    $timeStr = '昨日 ' . date('H:i', $actTime);
                                } else {
                                    $timeStr = date('Y/m/d H:i', $actTime);
                                }
                                $isLast = ($idx === count($recentActivities) - 1);
                            @endphp
                            <div class="list-group-item px-0 py-3 border-0 {{ !$isLast ? 'border-bottom' : '' }}">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold {{ $statusMeta['class'] }}">{{ $statusMeta['title'] }}</h6>
                                    <small class="text-muted">{{ $timeStr }}</small>
                                </div>
                                <p class="mb-1 small">
                                    <a href="{{ $detailUrl }}" class="text-decoration-none text-dark">
                                        {{ $statusMeta['desc'] }}
                                    </a>
                                </p>
                                <div class="d-flex justify-content-between align-items-center small text-muted flex-wrap gap-2">
                                    <span>取引: <a href="{{ $detailUrl }}" class="text-decoration-none fw-semibold">#{{ $act['transaction_id'] }} {{ $act['transaction_title'] }}</a></span>
                                    @if ($act['total_amount'] !== null)
                                        <span class="fw-semibold">金額: ¥{{ number_format((float)$act['total_amount']) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <!-- 対応が必要なタスク -->
        <div class="card card-custom mb-4 bg-white">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">対応が必要なタスク</h5>
                @php
                    $pendingLinkTasks = $pendingLinkTasks ?? [];
                    $totalTaskCount = count($pendingLinkTasks) + count($pendingTxTasks ?? []);
                @endphp
                @if ($totalTaskCount > 0)
                    <span class="badge bg-danger rounded-pill">{{ $totalTaskCount }}</span>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @if ($totalTaskCount === 0)
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-check2-circle fs-3 d-block text-success mb-2"></i>
                        現在、対応が必要なタスクはありません。
                    </div>
                @else
                    <!-- 1. つながり連携申請タスク -->
                    @foreach ($pendingLinkTasks as $task)
                        @php
                            $deadlineDate = date('Y/m/d', strtotime($task['link_created_at'] . ' +7 days'));
                            $isExpired = strtotime($deadlineDate) < strtotime(date('Y-m-d'));
                        @endphp
                        <a href="{{ route('tenant.link.index') }}" class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center">
                            <div style="font-size: 0.85rem;">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis mb-1" style="font-size: 0.7rem;">つながり</span>
                                <div class="fw-semibold text-dark">
                                    「{{ $task['partner_name'] }}」からの連携申請の承認判定
                                </div>
                                <div class="mt-1 font-monospace" style="font-size: 0.75rem;">
                                    <span class="{{ $isExpired ? 'text-danger fw-bold' : 'text-muted' }}">
                                        期限: {{ $deadlineDate }} {{ $isExpired ? '(超過)' : '' }}
                                    </span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    @endforeach

                    <!-- 2. 取引タスク（販売・購入） -->
                    @foreach ($pendingTxTasks as $task)
                        @php
                            $isSeller = $task['task_role'] === 'seller';
                            $deadline = !empty($task['desired_delivery_date']) ? date('Y/m/d', strtotime((string)$task['desired_delivery_date'])) : null;
                            $isExpired = $deadline ? (strtotime((string)$task['desired_delivery_date']) < strtotime(date('Y-m-d'))) : false;

                            if ($isSeller) {
                                $linkUrl = url('sell/edit/' . $task['id']);
                                $badgeClass = 'bg-danger-subtle text-danger-emphasis';
                                $badgeText = '販売';
                                $actionText = ($task['status'] === 'estimate_request') ? '見積依頼への回答' : '注文に対する受注承諾・回答';
                            } else {
                                $linkUrl = url('buy/detail/' . $task['id']);
                                $badgeClass = 'bg-primary-subtle text-primary-emphasis';
                                $badgeText = '購入';
                                $actionText = '見積回答の確認・注文手続き';
                            }
                        @endphp
                        <a href="{{ $linkUrl }}" class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center">
                            <div style="font-size: 0.85rem;">
                                <span class="badge {{ $badgeClass }} mb-1" style="font-size: 0.7rem;">{{ $badgeText }}</span>
                                <div class="fw-semibold text-dark">
                                    「{{ $task['partner_name'] }}」: {{ $task['title'] }} の{{ $actionText }}
                                </div>
                                <div class="mt-1 font-monospace" style="font-size: 0.75rem;">
                                    @if ($deadline)
                                        <span class="{{ $isExpired ? 'text-danger fw-bold' : 'text-muted' }}">
                                            希望納期: {{ $deadline }} {{ $isExpired ? '(超過)' : '' }}
                                        </span>
                                    @else
                                        <span class="text-muted">受付日: {{ date('Y/m/d', strtotime((string)$task['created_at'])) }}</span>
                                    @endif
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

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
