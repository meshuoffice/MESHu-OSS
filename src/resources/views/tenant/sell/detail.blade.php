@extends('layouts.tenant')

@section('title', 'MESHu - ' . ($title ?? '取引詳細 (販売)'))

@push('styles')
<style>
    .timeline-step {
        display: flex;
        align-items: center;
        position: relative;
        flex: 1;
    }
    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: calc(50% + 20px);
        width: calc(100% - 40px);
        height: 3px;
        background-color: #dee2e6;
        z-index: 1;
    }
    .timeline-step.completed:not(:last-child)::after {
        background-color: #198754;
    }
    .timeline-step.active:not(:last-child)::after {
        background-color: #ffc107;
    }
    .timeline-step.declined:not(:last-child)::after {
        background-color: #dee2e6;
    }
    .step-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin: 0 auto 0.5rem;
        position: relative;
        z-index: 2;
        background-color: #e9ecef;
        color: #6c757d;
    }
    .timeline-step.completed .step-icon {
        background-color: #198754;
        color: #fff;
    }
    .timeline-step.active .step-icon {
        background-color: #ffc107;
        color: #000;
        box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.25);
    }
    .timeline-step.declined .step-icon {
        background-color: #dc3545;
        color: #fff;
    }
    .table-items th {
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .comment-bubble {
        border-radius: 12px;
        padding: 1rem;
        position: relative;
        text-align: left;
        word-break: break-word;
    }
    .comment-bubble-mine {
        background-color: #e7f1ff;
        border: 1px solid #b6d4fe;
    }
    .comment-bubble-partner {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }
    .amount-summary-table td {
        padding: 0.5rem 0.75rem;
    }
</style>
@endpush

@section('content')
<!-- パンくずリスト & ページタイトル & アクションボタン -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">ホーム</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/sell') }}" class="text-decoration-none">販売 (売手)</a></li>
                <li class="breadcrumb-item active" aria-current="page">取引詳細 #{{ $tx['id'] }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 m-0 fw-bold">{{ $tx['title'] }}</h1>
            {!! \App\Models\Transaction::getBadgeHtml($tx['status'], 'seller', true, 'fs-6') !!}
        </div>
    </div>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1 shadow-sm px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-printer"></i>
                <span>印刷 / 帳票</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><h6 class="dropdown-header">帳票を選択して印刷</h6></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ url('/sell/print/' . $tx['id'] . '/estimate_request') }}" target="_blank">
                        <i class="bi bi-file-earmark-text text-primary"></i>
                        <span>見積依頼書 (買手発行)</span>
                    </a>
                </li>
                @if (in_array($tx['status'], ['estimate_replied', 'order_placed', 'order_accepted'], true))
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ url('/sell/print/' . $tx['id'] . '/estimate') }}" target="_blank">
                            <i class="bi bi-file-earmark-check text-info"></i>
                            <span>見積書 (売手発行)</span>
                        </a>
                    </li>
                @endif
                @if (in_array($tx['status'], ['order_placed', 'order_accepted'], true))
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ url('/sell/print/' . $tx['id'] . '/order') }}" target="_blank">
                            <i class="bi bi-file-earmark-arrow-up text-success"></i>
                            <span>発注書 (買手発行)</span>
                        </a>
                    </li>
                @endif
                @if ($tx['status'] === 'order_accepted')
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ url('/sell/print/' . $tx['id'] . '/order_acceptance') }}" target="_blank">
                            <i class="bi bi-file-earmark-lock text-dark"></i>
                            <span>注文請書 (売手発行)</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        @if ($tx['status'] === 'estimate_request')
            <a href="{{ url('/sell/estimate-decline/' . $tx['id']) }}" class="btn btn-outline-danger shadow-sm px-3">
                <i class="bi bi-x-circle me-1"></i>見積を辞退する
            </a>
            <a href="{{ url('/sell/edit/' . $tx['id']) }}" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm px-4 fw-bold">
                <i class="bi bi-pencil-square fs-6"></i>
                <span>明細・金額を入力・回答する</span>
            </a>
        @elseif ($tx['status'] === 'order_placed')
            <a href="{{ url('/sell/order-decline/' . $tx['id']) }}" class="btn btn-outline-danger shadow-sm px-3">
                <i class="bi bi-x-circle me-1"></i>注文を辞退する
            </a>
            <a href="{{ url('/sell/order-accept/' . $tx['id']) }}" class="btn btn-success d-inline-flex align-items-center gap-2 shadow-sm px-4 fw-bold">
                <i class="bi bi-check2-circle fs-6"></i>
                <span>受注を承諾する (取引成立)</span>
            </a>
        @endif
    </div>
</div>

<!-- 取引進捗ステッパー -->
@php $isDeclined = in_array($tx['status'], ['estimate_declined', 'order_declined'], true); @endphp
<div class="card card-custom bg-white shadow-sm mb-4">
    <div class="card-body py-4 px-3">
        <div class="d-flex justify-content-between text-center">
            @if ($isDeclined)
                <div class="timeline-step completed">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                        <div class="fw-bold small">1. 依頼受領</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ date('Y/m/d H:i', strtotime($tx['created_at'])) }}</div>
                    </div>
                </div>
                <div class="timeline-step declined">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi bi-x-lg"></i></div>
                        <div class="fw-bold small text-danger">2. {{ $tx['status'] === 'estimate_declined' ? '見積辞退' : '注文辞退' }} (終了)</div>
                        <div class="text-danger fw-semibold" style="font-size: 0.75rem;">{{ date('Y/m/d H:i', strtotime($tx['updated_at'])) }}</div>
                    </div>
                </div>
                <div class="timeline-step opacity-50">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi bi-cart"></i></div>
                        <div class="fw-bold small text-muted">3. 注文</div>
                        <div class="text-muted" style="font-size: 0.75rem;">-</div>
                    </div>
                </div>
                <div class="timeline-step opacity-50">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi bi-check-all"></i></div>
                        <div class="fw-bold small text-muted">4. 取引完了</div>
                        <div class="text-muted" style="font-size: 0.75rem;">-</div>
                    </div>
                </div>
            @else
                @php
                    $s1Completed = in_array($tx['status'], ['estimate_replied', 'order_placed', 'order_accepted'], true);
                    $s1Active    = $tx['status'] === 'estimate_request';
                    $s2Completed = in_array($tx['status'], ['order_placed', 'order_accepted'], true);
                    $s2Active    = $tx['status'] === 'estimate_replied';
                    $s3Completed = $tx['status'] === 'order_accepted';
                    $s3Active    = $tx['status'] === 'order_placed';
                    $s4Completed = $tx['status'] === 'order_accepted';
                @endphp
                <div class="timeline-step {{ $s1Completed ? 'completed' : ($s1Active ? 'active' : '') }}">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi {{ $s1Completed ? 'bi-check-lg' : 'bi-file-earmark-text' }}"></i></div>
                        <div class="fw-bold small {{ $s1Active ? 'text-warning-emphasis' : '' }}">1. 見積依頼 (受信)</div>
                        <div class="{{ $s1Active ? 'text-warning-emphasis fw-semibold' : 'text-muted' }}" style="font-size: 0.75rem;">{{ date('Y/m/d H:i', strtotime($tx['created_at'])) }}</div>
                    </div>
                </div>
                <div class="timeline-step {{ $s2Completed ? 'completed' : ($s2Active ? 'active' : '') }}">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi {{ $s2Completed ? 'bi-check-lg' : 'bi-chat-dots' }}"></i></div>
                        <div class="fw-bold small {{ $s2Active ? 'text-warning-emphasis' : '' }}">2. 見積回答</div>
                        <div class="{{ $s2Active ? 'text-warning-emphasis fw-semibold' : 'text-muted' }}" style="font-size: 0.75rem;">
                            {{ $s2Completed ? date('Y/m/d H:i', strtotime($tx['updated_at'])) : ($s1Active ? '要回答' : '未完了') }}
                        </div>
                    </div>
                </div>
                <div class="timeline-step {{ $s3Completed ? 'completed' : ($s3Active ? 'active' : '') }}">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi {{ $s3Completed ? 'bi-check-lg' : 'bi-cart' }}"></i></div>
                        <div class="fw-bold small {{ $s3Active ? 'text-warning-emphasis' : '' }}">3. 注文 (発注)</div>
                        <div class="{{ $s3Active ? 'text-warning-emphasis fw-semibold' : 'text-muted' }}" style="font-size: 0.75rem;">
                            {{ $s3Completed ? date('Y/m/d H:i', strtotime($tx['updated_at'])) : ($s3Active ? '要確認' : '未完了') }}
                        </div>
                    </div>
                </div>
                <div class="timeline-step {{ $s4Completed ? 'completed' : '' }}">
                    <div class="step-content w-100">
                        <div class="step-icon"><i class="bi bi-check2-all"></i></div>
                        <div class="fw-bold small {{ $s4Completed ? 'text-success' : 'text-muted' }}">4. 注文回答 (完了)</div>
                        <div class="{{ $s4Completed ? 'text-success fw-semibold' : 'text-muted' }}" style="font-size: 0.75rem;">
                            {{ $s4Completed ? date('Y/m/d H:i', strtotime($tx['updated_at'])) : 'ゴール' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- 辞退理由通知カード（辞退時のみ） -->
@if ($isDeclined)
    <div class="card card-custom bg-white shadow-sm mb-4 border-danger border-opacity-50">
        <div class="card-header bg-danger-subtle py-3 border-bottom border-danger-subtle d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon-fill text-danger fs-5"></i>
            <h5 class="mb-0 fw-bold text-danger">自社による辞退記録</h5>
            <span class="badge bg-danger ms-auto">取引終了（ロック）</span>
        </div>
        <div class="card-body p-4">
            <div class="row mb-3">
                <div class="col-md-3 text-muted fw-bold">辞退理由種別</div>
                <div class="col-md-9">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 px-3 py-1">
                        {{ $tx['decline_category'] ?? '辞退理由記載なし' }}
                    </span>
                </div>
            </div>
            @if (!empty($tx['decline_reason']))
                <div class="row mb-3">
                    <div class="col-md-3 text-muted fw-bold">送信したメッセージ</div>
                    <div class="col-md-9">
                        <div class="p-3 bg-light rounded border text-secondary small lh-base" style="white-space: pre-wrap;">{{ $tx['decline_reason'] }}</div>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-3 text-muted fw-bold">辞退日時</div>
                <div class="col-md-9 font-monospace small text-muted">
                    {{ date('Y年m月d日 H:i', strtotime($tx['updated_at'])) }}
                </div>
            </div>
        </div>
    </div>
@endif

<!-- メイン 2カラム構成 -->
<div class="row g-4">
    <!-- 左メインカラム (8) -->
    <div class="col-lg-8">

        <!-- 1. 見積依頼明細テーブル -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-list-check me-2 text-primary"></i>{{ $tx['status'] === 'estimate_request' ? '見積依頼明細' : ($tx['status'] === 'order_placed' ? '注文明細' : '取引明細') }}
                    </h5>
                    @if (!$isDeclined && $tx['status'] !== 'order_accepted')
                        <a href="{{ url('/sell/edit/' . $tx['id']) }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-2 py-1">
                            <i class="bi bi-pencil-square"></i>
                            <span>編集する</span>
                        </a>
                    @endif
                </div>
                @if ($tx['total_amount'] === null)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        {{ $tx['status'] === 'estimate_request' ? '金額未入力 (要見積回答)' : '金額未入力' }}
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary font-monospace">明細 {{ count($items ?? []) }} 件</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-items align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">No.</th>
                                <th>品名・型番・仕様</th>
                                <th style="width: 120px;" class="text-end">{{ $tx['status'] === 'estimate_request' ? '希望数量' : '数量' }}</th>
                                <th style="width: 80px;" class="text-center">単位</th>
                                <th style="width: 140px;" class="text-end">{{ $tx['status'] === 'estimate_request' ? '提示単価 (税抜)' : '単価 (税抜)' }}</th>
                                <th style="width: 140px;" class="text-end">金額 (税抜)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (empty($items))
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">明細データがありません。</td>
                                </tr>
                            @else
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
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace fw-semibold">
                                            @if ($item['amount'] !== null)
                                                ¥{{ number_format((float)$item['amount']) }}
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

                @if ($tx['total_amount'] === null && !$isDeclined && $tx['status'] !== 'order_accepted')
                    <div class="p-3 bg-light border-top text-end small text-muted">
                        ※単価・値引き・送料・適用消費税率は「<a href="{{ url('/sell/edit/' . $tx['id']) }}" class="fw-bold text-primary">編集する</a>」画面より入力してください。
                    </div>
                @elseif ($tx['total_amount'] !== null)
                    <!-- 金額サマリーエリア -->
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
                                <td class="text-end font-monospace fw-bold fs-4 text-primary">¥{{ number_format((float)($tx['total_amount'] ?? 0)) }}</td>
                            </tr>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- 添付ファイル -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-paperclip me-2 text-primary"></i>添付ファイル</h5>
                <span class="badge bg-secondary-subtle text-secondary">{{ count($attachments ?? []) }} 件</span>
            </div>
            <div class="card-body p-3">
                @if (empty($attachments))
                    <div class="text-muted small py-2">添付されているファイルはありません。</div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($attachments as $att)
                            <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-arrow-down-fill text-primary fs-4"></i>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $att['file_name'] }}</div>
                                        <div class="small text-muted">
                                            {{ round($att['file_size'] / 1024, 1) }} KB ・ {{ date('Y/m/d H:i', strtotime($att['created_at'])) }}
                                            @if (!empty($att['company_name']))
                                                ({{ $att['company_name'] }}{{ !empty($att['user_name']) ? ': ' . $att['user_name'] : '' }})
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ url('/sell/attachment/' . $att['id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>ダウンロード
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- 取引コメント -->
        <div class="card card-custom bg-white shadow-sm mb-4" id="comments">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-dots-fill me-2 text-primary"></i>取引メッセージ・コメント</h5>
                <span class="badge bg-primary-subtle text-primary">{{ count($comments ?? []) }} 件のメッセージ</span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3 mb-4">
                    @if (empty($comments))
                        <div class="text-muted small text-center py-3">コメントのやり取りはまだありません。</div>
                    @else
                        @foreach ($comments as $cmt)
                            @php
                                $isMine = (int)$cmt['company_id'] === (int)$myCompanyId;
                                $isAuthor = $isMine && (int)($cmt['user_id'] ?? 0) === (int)($myUserId ?? auth()->id());
                            @endphp
                            <div class="d-flex flex-column {{ $isMine ? 'align-items-end' : 'align-items-start' }}">
                                <div class="small text-muted mb-1 d-flex align-items-center gap-1">
                                    <span class="badge {{ $isMine ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} me-1">
                                        {{ $isMine ? '自社' : ($cmt['company_name'] ?? '相手企業') }}
                                    </span>
                                    {{ $cmt['user_name'] ?? '退職ユーザー' }} ・ {{ date('Y/m/d H:i', strtotime($cmt['created_at'])) }}
                                    @if ($isAuthor)
                                        <form method="post" action="{{ url('/sell/comment/delete/' . $cmt['id']) }}" class="d-inline ms-1" onsubmit="return confirm('このメッセージを削除しますか？');">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0 text-secondary text-danger-hover align-middle" title="メッセージを削除" style="font-size: 0.85rem; text-decoration: none;">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="comment-bubble {{ $isMine ? 'comment-bubble-mine' : 'comment-bubble-partner' }}" style="max-width: 80%; white-space: pre-wrap; text-align: left;">{{ trim($cmt['comment']) }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <form method="post" action="{{ url('/sell/comment/' . $tx['id']) }}" class="border-top pt-3">
                    @csrf
                    <label for="commentInput" class="form-label fw-semibold small text-dark">新しいメッセージを投稿</label>
                    <textarea name="comment" class="form-control mb-2" id="commentInput" rows="3" placeholder="買手企業への連絡事項や確認内容を入力してください..." required></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                            <i class="bi bi-send-fill me-1"></i>メッセージ送信
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- 右サイドカラム (4) -->
    <div class="col-lg-4">

        <!-- 取引情報 -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle-fill me-2 text-primary"></i>取引情報</h5>
            </div>
            <div class="card-body p-3">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">取引ID</dt>
                    <dd class="col-sm-8 font-monospace fw-bold">#{{ $tx['id'] }}</dd>

                    <dt class="col-sm-4 text-muted">ステータス</dt>
                    <dd class="col-sm-8">
                        {!! \App\Models\Transaction::getBadgeHtml($tx['status'], 'seller', false) !!}
                    </dd>

                    <dt class="col-sm-4 text-muted">希望納期</dt>
                    <dd class="col-sm-8 font-monospace">
                        {{ !empty($tx['desired_delivery_date']) ? date('Y/m/d', strtotime($tx['desired_delivery_date'])) : '-' }}
                    </dd>

                    <dt class="col-sm-4 text-muted">確定納期</dt>
                    <dd class="col-sm-8 font-monospace {{ !empty($tx['delivery_date']) ? 'fw-bold text-success' : 'text-muted' }}">
                        {{ !empty($tx['delivery_date']) ? date('Y/m/d', strtotime($tx['delivery_date'])) : '未確定' }}
                    </dd>

                    <dt class="col-sm-4 text-muted">起票日時</dt>
                    <dd class="col-sm-8">{{ date('Y/m/d H:i', strtotime($tx['created_at'])) }}</dd>

                    <dt class="col-sm-4 text-muted">最終更新</dt>
                    <dd class="col-sm-8">{{ date('Y/m/d H:i', strtotime($tx['updated_at'])) }}</dd>
                </dl>
            </div>
        </div>

        <!-- 購入元企業 (買手) 情報 -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-building-fill me-2 text-primary"></i>購入元企業 (買手)</h5>
            </div>
            <div class="card-body p-3 small">
                <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-1 mb-2">
                    {{ $tx['partner_name'] }}
                    @if (!empty($tx['partner_badge_status']) && $tx['partner_badge_status'] === 'approved')
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">認証済</span>
                    @endif
                </div>
                <div class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>{{ ($tx['partner_city'] ?? '') . ($tx['partner_address'] ?? '') }}</div>
                @if (!empty($tx['partner_tel']))
                    <div class="text-muted mb-1"><i class="bi bi-telephone me-1"></i>{{ $tx['partner_tel'] }}</div>
                @endif
                <a href="{{ url('/link/detail/' . $tx['company_link_id']) }}" class="btn btn-outline-secondary btn-sm w-100 mt-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i>企業詳細プロフィールを見る
                </a>
            </div>
        </div>

        <!-- 買手指示事項 -->
        @if (!empty($tx['buyer_remarks']))
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-left-text me-2 text-primary"></i>買手備考（相手先指示事項）</h5>
                </div>
                <div class="card-body p-3 small text-secondary bg-light" style="white-space: pre-wrap;">{{ $tx['buyer_remarks'] }}</div>
            </div>
        @endif

        <!-- 自社備考 -->
        @if (!empty($tx['seller_remarks']))
            <div class="card card-custom bg-white shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-right-text me-2 text-info"></i>売手備考（自社連絡事項）</h5>
                </div>
                <div class="card-body p-3 small text-secondary bg-light" style="white-space: pre-wrap;">{{ $tx['seller_remarks'] }}</div>
            </div>
        @endif

        <!-- ステータス履歴 -->
        <div class="card card-custom bg-white shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>ステータス履歴</h5>
            </div>
            <div class="card-body p-3">
                @if (empty($histories))
                    <div class="text-muted small">履歴情報はありません。</div>
                @else
                    <ul class="list-unstyled small mb-0">
                        @foreach ($histories as $h)
                            <li class="mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-secondary-subtle text-secondary">{{ \App\Models\Transaction::getLabel($h['status']) }}</span>
                                    <span class="text-muted">{{ date('m/d H:i', strtotime($h['created_at'])) }}</span>
                                </div>
                                <div class="text-dark fw-semibold mt-1">
                                    {{ $h['company_name'] ?? '' }}: {{ $h['user_name'] ?? 'システム' }}
                                </div>
                                @if (!empty($h['note']))
                                    <div class="text-muted mt-1 small bg-light p-1 rounded">{{ $h['note'] }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
